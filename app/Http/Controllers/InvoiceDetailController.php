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
use App\Models\WarehouseInventoryBalance;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Models\ProductBatch;
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
        $warehouseItems = \App\Models\Warehouse::where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('invoice_details.create',compact('warehouseItems'));
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
            'warehouse_id' => 'required|exists:warehouses,id', 
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
                // Check warehouse inventory instead of main batch quantity
                $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $input['warehouse_id'])
                    ->where('batch_id', $input['product_batch_id'])
                    ->first();
                
                if (!$warehouseInventory || $warehouseInventory->quantity < $input['quantity']) {
                    $available = $warehouseInventory ? $warehouseInventory->quantity : 0;
                    throw new \Exception('Insufficient quantity in warehouse. Available: ' . $available);
                }

                $input['totalprice'] = $input['quantity'] * $input['price'];
                $invoiceDetail = $this->invoiceDetailRepository->create($input);
                
                // Deduct quantity from warehouse inventory
                $warehouseInventory->decreaseQuantity($input['quantity']);
                
                // Deduct quantity from product batch (global stock)
                $batch->decreaseQuantity($input['quantity'], 'Invoice - [' . $invoice->invoiceno . '] - New detail added');
                
                // Create inventory transaction for stock out from warehouse
                InventoryTransaction::create([
                    'type' => 2, // Stock Out
                    'warehouse_id' => $input['warehouse_id'],
                    'product_id' => $input['product_id'],
                    'batch_id' => $input['product_batch_id'],
                    'quantity' => -$input['quantity'],
                    'date' => now(),
                    'user' => Auth::user()->name,
                    'remark' => 'Invoice - [' . $invoice->invoiceno . '] - New detail added from warehouse'
                ]);
                
            } else {
                // UPDATE MODE - Need to handle warehouse inventory adjustments
                $existingDetail = $this->invoiceDetailRepository->find($input['edit_id']);
                
                if (empty($existingDetail)) {
                    throw new \Exception('Invoice detail not found');
                }

                // Check if warehouse, batch or quantity changed
                $warehouseChanged = ($existingDetail->warehouse_id != $input['warehouse_id']);
                $batchChanged = ($existingDetail->product_batch_id != $input['product_batch_id']);
                $quantityChanged = ($existingDetail->quantity != $input['quantity']);
                
                if ($warehouseChanged || $batchChanged || $quantityChanged) {
                    // CASE 1: Return stock to original warehouse AND original batch
                    if ($existingDetail->warehouse_id && $existingDetail->product_batch_id) {
                        $oldWarehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $existingDetail->warehouse_id)
                            ->where('batch_id', $existingDetail->product_batch_id)
                            ->first();
                        
                        if ($oldWarehouseInventory) {
                            $oldWarehouseInventory->increaseQuantity($existingDetail->quantity);
                            
                            // Create inventory transaction for reversal
                            InventoryTransaction::create([
                                'type' => 1, // Stock In (Reversal)
                                'warehouse_id' => $existingDetail->warehouse_id,
                                'product_id' => $existingDetail->product_id,
                                'batch_id' => $existingDetail->product_batch_id,
                                'quantity' => $existingDetail->quantity,
                                'date' => now(),
                                'user' => Auth::user()->name,
                                'remark' => 'Reversal - Updating invoice -[' . $invoice->invoiceno . ']'
                            ]);
                        }
                        
                        // Return stock to the original product batch (global stock)
                        $oldBatch = ProductBatch::find($existingDetail->product_batch_id);
                        if ($oldBatch) {
                            $oldBatch->increaseQuantity($existingDetail->quantity, 'Reversal - Updating invoice -[' . $invoice->invoiceno . ']');
                        }
                    }

                    // CASE 2: Check new warehouse has enough stock
                    $newWarehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $input['warehouse_id'])
                        ->where('batch_id', $input['product_batch_id'])
                        ->first();
                    
                    if (!$newWarehouseInventory || $newWarehouseInventory->quantity < $input['quantity']) {
                        $available = $newWarehouseInventory ? $newWarehouseInventory->quantity : 0;
                        throw new \Exception('Insufficient quantity in new warehouse. Available: ' . $available);
                    }

                    // Deduct from new warehouse
                    $newWarehouseInventory->decreaseQuantity($input['quantity']);
                    
                    // Deduct from new product batch (global stock)
                    $newBatch = ProductBatch::find($input['product_batch_id']);
                    if ($newBatch) {
                        $newBatch->decreaseQuantity($input['quantity'], 'Updated invoice -[' . $invoice->invoiceno . ']');
                    }
                    
                    // Create inventory transaction for new stock out
                    InventoryTransaction::create([
                        'type' => 2, // Stock Out
                        'warehouse_id' => $input['warehouse_id'],
                        'product_id' => $input['product_id'],
                        'batch_id' => $input['product_batch_id'],
                        'quantity' => -$input['quantity'],
                        'date' => now(),
                        'user' => Auth::user()->email . ' (' . Auth::user()->name . ')',
                        'remark' => 'Updated invoice -[' . $invoice->invoiceno . ']' 
                    ]);
                } else {
                    // Only quantity changed but same warehouse and batch
                    if ($quantityChanged) {
                        // Calculate the difference
                        $quantityDifference = $input['quantity'] - $existingDetail->quantity;
                        
                        if ($quantityDifference > 0) {
                            // Need to deduct more stock
                            $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $input['warehouse_id'])
                                ->where('batch_id', $input['product_batch_id'])
                                ->first();
                            
                            if (!$warehouseInventory || $warehouseInventory->quantity < $quantityDifference) {
                                $available = $warehouseInventory ? $warehouseInventory->quantity : 0;
                                throw new \Exception('Insufficient quantity in warehouse. Available: ' . $available);
                            }
                            
                            $warehouseInventory->decreaseQuantity($quantityDifference);
                            
                            // Deduct from product batch
                            $batch = ProductBatch::find($input['product_batch_id']);
                            if ($batch) {
                                $batch->decreaseQuantity($quantityDifference, 'Updated invoice quantity -[' . $invoice->invoiceno . ']');
                            }
                            
                            InventoryTransaction::create([
                                'type' => 2,
                                'warehouse_id' => $input['warehouse_id'],
                                'product_id' => $input['product_id'],
                                'batch_id' => $input['product_batch_id'],
                                'quantity' => -$quantityDifference,
                                'date' => now(),
                                'user' => Auth::user()->name,
                                'remark' => 'Increased quantity for invoice -[' . $invoice->invoiceno . ']'
                            ]);
                        } elseif ($quantityDifference < 0) {
                            // Return excess stock
                            $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $input['warehouse_id'])
                                ->where('batch_id', $input['product_batch_id'])
                                ->first();
                            
                            if ($warehouseInventory) {
                                $warehouseInventory->increaseQuantity(abs($quantityDifference));
                                
                                // Return to product batch
                                $batch = ProductBatch::find($input['product_batch_id']);
                                if ($batch) {
                                    $batch->increaseQuantity(abs($quantityDifference), 'Reduced invoice quantity -[' . $invoice->invoiceno . ']');
                                }
                                
                                InventoryTransaction::create([
                                    'type' => 1,
                                    'warehouse_id' => $input['warehouse_id'],
                                    'product_id' => $input['product_id'],
                                    'batch_id' => $input['product_batch_id'],
                                    'quantity' => abs($quantityDifference),
                                    'date' => now(),
                                    'user' => Auth::user()->name,
                                    'remark' => 'Decreased quantity for invoice -[' . $invoice->invoiceno . ']'
                                ]);
                            }
                        }
                    }
                }

                // Update the invoice detail
                $input['totalprice'] = $input['quantity'] * $input['price'];
                $invoiceDetail = $this->invoiceDetailRepository->update($input, $input['edit_id']);
            }

            DB::commit();

            if ($is_store == true) {
                Flash::success(__('Invoices Detail saved successfully'));
            } else {
                Flash::success(__('Invoices Detail updated successfully'));
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
        $warehouseItems = \App\Models\Warehouse::where('status', 'active')
                    ->orderBy('name')
                    ->pluck('name', 'id');

        if (empty($invoiceDetail)) {
            Flash::error(__('invoices_details.invoice_detail_not_found'));

            return redirect(route('invoiceDetails.index'));
        }

        return view('invoice_details.edit',compact('invoiceDetail', 'warehouseItems'));
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

        DB::beginTransaction();
        try {
            // Return stock to warehouse and product batch before deleting
            if ($invoiceDetail->warehouse_id && $invoiceDetail->product_batch_id && $invoiceDetail->quantity > 0) {
                // Return to warehouse inventory
                $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $invoiceDetail->warehouse_id)
                    ->where('batch_id', $invoiceDetail->product_batch_id)
                    ->first();
                
                if ($warehouseInventory) {
                    $warehouseInventory->increaseQuantity($invoiceDetail->quantity);
                }
                
                // Return to product batch (global stock)
                $batch = ProductBatch::find($invoiceDetail->product_batch_id);
                if ($batch) {
                    $batch->increaseQuantity($invoiceDetail->quantity, 'Deleted invoice detail - Stock returned');
                }
                
                // Create inventory transaction for return
                $invoice = Invoice::find($invoiceDetail->invoice_id);
                InventoryTransaction::create([
                    'type' => 1, // Stock In
                    'warehouse_id' => $invoiceDetail->warehouse_id,
                    'product_id' => $invoiceDetail->product_id,
                    'batch_id' => $invoiceDetail->product_batch_id,
                    'quantity' => $invoiceDetail->quantity,
                    'date' => now(),
                    'user' => Auth::user()->name,
                    'remark' => 'Deleted invoice detail from invoice - [' . ($invoice ? $invoice->invoiceno : 'N/A') . ']'
                ]);
            }
            
            $this->invoiceDetailRepository->delete($id);
            DB::commit();
            
            Flash::success(__('invoices_details.invoice_detail_deleted_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error deleting invoice detail: ' . $e->getMessage());
        }

        return redirect(route('invoiceDetails.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;
        
        DB::beginTransaction();
        try {
            foreach ($ids as $id) {
                $invoiceDetail = $this->invoiceDetailRepository->find($id);
                
                if ($invoiceDetail) {
                    // Return stock to warehouse and product batch
                    if ($invoiceDetail->warehouse_id && $invoiceDetail->product_batch_id && $invoiceDetail->quantity > 0) {
                        $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $invoiceDetail->warehouse_id)
                            ->where('batch_id', $invoiceDetail->product_batch_id)
                            ->first();
                        
                        if ($warehouseInventory) {
                            $warehouseInventory->increaseQuantity($invoiceDetail->quantity);
                        }
                        
                        $batch = ProductBatch::find($invoiceDetail->product_batch_id);
                        if ($batch) {
                            $batch->increaseQuantity($invoiceDetail->quantity, 'Mass deleted invoice details - Stock returned');
                        }
                    }
                    
                    $count += InvoiceDetail::destroy($id);
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    
        return $count;
    }
    
    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = InvoiceDetail::whereIn('id',$ids)->update(['status'=>$status]);
    
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