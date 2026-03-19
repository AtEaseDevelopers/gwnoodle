<?php

namespace App\Http\Controllers;

use App\DataTables\InvoiceDetailDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInvoiceDetailRequest;
use App\Http\Requests\UpdateInvoiceDetailRequest;
use App\Repositories\InvoiceDetailRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\InvoiceDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\SpecialPrice;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Models\ProductBatch;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Validator;


class InvoiceDetailController extends AppBaseController
{
    /** @var InvoiceDetailRepository $invoiceDetailRepository*/
    private $invoiceDetailRepository;

    public function __construct(InvoiceDetailRepository $invoiceDetailRepo)
    {
        $this->invoiceDetailRepository = $invoiceDetailRepo;
    }

    /**
     * Display a listing of the InvoiceDetail.
     *
     * @param InvoiceDetailDataTable $invoiceDetailDataTable
     *
     * @return Response
     */
    public function index(Request $req, InvoiceDetailDataTable $invoiceDetailDataTable)
    {
        $this->upsertDetail(Session::get('is_store'), $req);

        return $invoiceDetailDataTable->render('invoice_details.index');
    }

    /**
     * Show the form for creating a new InvoiceDetail.
     *
     * @return Response
     */
    public function create()
    {
        return view('invoice_details.create');
    }

    /**
     * Store a newly created InvoiceDetail in storage.
     *
     * @param CreateInvoiceDetailRequest $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'invoice_id' => 'required|exists:invoices,id',
            'product_id' => 'required|exists:products,id',
            'product_batch_id' => 'required|exists:product_batches,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $input = $request->all();

        // Check if batch has enough quantity
        $batch = ProductBatch::find($input['product_batch_id']);
        if ($batch->quantity < $input['quantity']) {
            Flash::error('Insufficient batch quantity. Available: ' . $batch->quantity);
            return redirect()->back()->withInput();
        }

        Session::put('is_store', true);
        Session::put('invoice_detail_data', $input);

        $this->upsertDetail(true, $request);

        return redirect(route('invoiceDetails.index'));
    }
    
    private function upsertDetail($is_store, Request $req) 
    {
        $input = Session::get('invoice_detail_data');
        if ($input == null) {
            return null;
        }

        $invoice = Invoice::where('id', $input['invoice_id'])->first();
        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect()->back();
        }

        DB::beginTransaction();

        try {
            if ($is_store == true) {
                // CREATE MODE - Add new detail
                $batch = ProductBatch::find($input['product_batch_id']);
                
                if ($batch->quantity < $input['quantity']) {
                    throw new \Exception('Insufficient batch quantity. Available: ' . $batch->quantity);
                }

                $input['totalprice'] = $input['quantity'] * $input['price'];
                $invoiceDetail = $this->invoiceDetailRepository->create($input);
                
                // Deduct quantity from batch
                $batch->decrement('quantity', $input['quantity']);
                
                // Create inventory transaction for stock out
                InventoryTransaction::create([
                    'type' => 2, // Stock Out
                    'product_id' => $input['product_id'],
                    'batch_id' => $input['product_batch_id'],
                    'quantity' => -$input['quantity'],
                    'date' => now(),
                    'user' => Auth::user()->email . ' (' . Auth::user()->name . ')',
                    'remark' => 'Invoice #' . $invoice->invoiceno . ' - New detail added'
                ]);
                
            } else {
                // UPDATE MODE - Need to handle batch adjustments
                $existingDetail = $this->invoiceDetailRepository->find($input['edit_id']);
                
                if (empty($existingDetail)) {
                    throw new \Exception('Invoice detail not found');
                }

                // Check if batch or quantity changed
                $batchChanged = ($existingDetail->product_batch_id != $input['product_batch_id']);
                $quantityChanged = ($existingDetail->quantity != $input['quantity']);
                
                if ($batchChanged || $quantityChanged) {
                    // CASE 1: Return stock to original batch
                    $oldBatch = ProductBatch::find($existingDetail->product_batch_id);
                    if ($oldBatch) {
                        $oldBatch->increment('quantity', $existingDetail->quantity);
                        
                        // Create inventory transaction for reversal
                        InventoryTransaction::create([
                            'type' => 1, // Stock In (Reversal)
                            'product_id' => $existingDetail->product_id,
                            'batch_id' => $existingDetail->product_batch_id,
                            'quantity' => $existingDetail->quantity,
                            'date' => now(),
                            'user' => Auth::user()->email . ' (' . Auth::user()->name . ')',
                            'remark' => 'Reversal - Updating invoice #' . $invoice->invoiceno
                        ]);
                    }

                    // CASE 2: Check new batch has enough stock
                    $newBatch = ProductBatch::find($input['product_batch_id']);
                    if ($newBatch->quantity < $input['quantity']) {
                        throw new \Exception('Insufficient quantity in new batch. Available: ' . $newBatch->quantity);
                    }

                    // Deduct from new batch
                    $newBatch->decrement('quantity', $input['quantity']);
                    
                    // Create inventory transaction for new stock out
                    InventoryTransaction::create([
                        'type' => 2, // Stock Out
                        'product_id' => $input['product_id'],
                        'batch_id' => $input['product_batch_id'],
                        'quantity' => -$input['quantity'],
                        'date' => now(),
                        'user' => Auth::user()->email . ' (' . Auth::user()->name . ')',
                        'remark' => 'Updated invoice #' . $invoice->invoiceno
                    ]);
                } else {
                    // No batch change, only quantity changed? This shouldn't happen with your UI
                    // But just in case, handle quantity adjustment
                    if ($input['quantity'] != $existingDetail->quantity) {
                        $batch = ProductBatch::find($input['product_batch_id']);
                        $quantityDiff = $input['quantity'] - $existingDetail->quantity;
                        
                        if ($quantityDiff > 0) {
                            // Need more stock - check availability
                            if ($batch->quantity < $quantityDiff) {
                                throw new \Exception('Insufficient batch quantity. Available: ' . $batch->quantity . ', Need: ' . $quantityDiff);
                            }
                            $batch->decrement('quantity', $quantityDiff);
                        } else {
                            // Returning stock
                            $batch->increment('quantity', abs($quantityDiff));
                        }
                        
                        // Create inventory transaction for adjustment
                        InventoryTransaction::create([
                            'type' => ($quantityDiff > 0) ? 2 : 1, // Stock Out if positive, Stock In if negative
                            'product_id' => $input['product_id'],
                            'batch_id' => $input['product_batch_id'],
                            'quantity' => -$quantityDiff,
                            'date' => now(),
                            'user' => Auth::user()->email . ' (' . Auth::user()->name . ')',
                            'remark' => 'Quantity adjustment - Invoice #' . $invoice->invoiceno
                        ]);
                    }
                }

                // Update the invoice detail
                $input['totalprice'] = $input['quantity'] * $input['price'];
                $invoiceDetail = $this->invoiceDetailRepository->update($input, $input['edit_id']);
            }

            DB::commit();

            if ($is_store == true) {
                Flash::success(__('invoices_details.invoice_detail_saved_successfully'));
            } else {
                Flash::success(__('invoices_details.invoice_detail_updated_successfully'));
            }
            
            Session::forget('invoice_detail_data');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified InvoiceDetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $invoiceDetail = $this->invoiceDetailRepository->find($id);

        if (empty($invoiceDetail)) {
            Flash::error(__('invoices_details.invoice_detail_not_found'));

            return redirect(route('invoiceDetails.index'));
        }

        return view('invoice_details.show')->with('invoiceDetail', $invoiceDetail);
    }

    /**
     * Show the form for editing the specified InvoiceDetail.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $invoiceDetail = $this->invoiceDetailRepository->find($id);

        if (empty($invoiceDetail)) {
            Flash::error(__('invoices_details.invoice_detail_not_found'));

            return redirect(route('invoiceDetails.index'));
        }

        return view('invoice_details.edit')->with('invoiceDetail', $invoiceDetail);
    }

    /**
     * Update the specified InvoiceDetail in storage.
     *
     * @param int $id
     * @param UpdateInvoiceDetailRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInvoiceDetailRequest $request)
    {
        $id = Crypt::decrypt($id);
        $invoiceDetail = $this->invoiceDetailRepository->find($id);

        if (empty($invoiceDetail)) {
            Flash::error(__('invoices_details.invoice_detail_not_found'));
            return redirect(route('invoiceDetails.index'));
        }

        $input = $request->all();
        $input['edit_id'] = $id; // Make sure this is set
        
        // Validate batch quantity before proceeding
        $batch = ProductBatch::find($input['product_batch_id']);
        if ($batch->quantity < $input['quantity']) {
            Flash::error('Insufficient batch quantity. Available: ' . $batch->quantity);
            return redirect()->back()->withInput();
        }
        
        Session::put('is_store', false);
        Session::put('invoice_detail_data', $input);

        $this->upsertDetail(false, $request);

        return redirect(route('invoiceDetails.index'));
    }

    /**
     * Remove the specified InvoiceDetail from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $invoiceDetail = $this->invoiceDetailRepository->find($id);

        if (empty($invoiceDetail)) {
            Flash::error(__('invoices_details.invoice_detail_not_found'));

            return redirect(route('invoiceDetails.index'));
        }

        $this->invoiceDetailRepository->delete($id);
        Flash::success(__('invoices_details.invoice_detail_deleted_successfully'));

        return redirect(route('invoiceDetails.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;
    
        foreach ($ids as $id) {
            
            $invoicedetail = $this->invoiceDetailRepository->find($id);
    
            $count = $count + invoicedetail::destroy($id);
        }
    
        return $count;
    }
    
    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = invoicedetail::whereIn('id',$ids)->update(['status'=>$status]);
    
        return $count;
    }


    public function getprices($invoice_id,$product_id)
    {   
        $invoice = Invoice::where('id',$invoice_id)->first();

        if (empty($invoice)) {
            return response()->json(['status' => false, 'message' => 'Invoice not found!']);
        }

        $product = Product::where('id',$product_id)->first();

        if (empty($product)) {
            return response()->json(['status' => false, 'message' => 'Product not found!']);
        }

        $specialprice = SpecialPrice::where('customer_id',$invoice->customer_id)->where('product_id',$product_id)->first();

        if (empty($specialprice)) {
            return response()->json(['status' => true, 'message' => 'Special Price not found!', 'data' => $product->price]);
        } else {
            return response()->json(['status' => true, 'message' => 'Special Price found!', 'data' => $specialprice->price]);
        }
    }
}