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
    public function store(CreateInvoiceDetailRequest $request)
    {
        $input = $request->all();
        Session::put('is_store', true);
        Session::put('invoice_detail_data', $input);

        $res = $this->upsertDetail(true, $request);
        if ($res != null) {
            return $res;
        }
        
        return redirect(route('invoiceDetails.index'));
    }
    
    private function upsertDetail($is_store, Request $req) {
        $input = Session::get('invoice_detail_data');
        if ($input == null) {
            return null;
        }

        $invoice = Invoice::where('id', $input['invoice_id'])->first();

        DB::beginTransaction();

        if ($is_store == true) {
            $input['totalprice'] = $input['quantity'] * $input['price'];
            $invoiceDetail = $this->invoiceDetailRepository->create($input);
        } else {
            $input['totalprice'] = $input['quantity'] * $input['price'];
            $invoiceDetail = $this->invoiceDetailRepository->update($input, $input['edit_id']);
        }

        // Create credit note in Xero if payment term is credit
        $xero_has_err = false;
        if ($invoice != null && $invoice->paymentterm == 2) {
            try {
                $redirect_uri = config('app.url') . '/invoiceDetails';
                $xero = new XeroController($redirect_uri);
    
                // Get Xero's access token
                if ($req->has('code')) {
                    $res = $xero->getToken($req->code);
                    if (!$res->ok()) {
                        throw new Exception('Failed to get xero access token.');
                    }
                }
                // Xero auth
                $res = $xero->auth();
                if ($res !== true) {
                    return $res;
                }
                // Get contact
                $customer_name = Customer::where('id', $invoice->customer_id)->value('company');
                
                $res = $xero->getContact($customer_name); // Get contact
                $payload = $res->object();

                if (!$res->ok()) {
                    throw new Exception('Failed to get xero contact.');
                } elseif ($res->ok() && isset($payload->Contacts) && count($payload->Contacts) <= 0) { // Create contact in Xero
                    $res = $xero->createContact($customer_name);
                    if (!$res->ok()) {
                        throw new Exception('Failed to create contact for ' . $customer_name);
                    }
                    $payload = $res->object();
                }
                // Create credit note
                $items = [
                    'Quantity' => $input['quantity'],
                    'UnitAmount' => $input['price'], 
                    'Description' => $input['remark'] ?? $invoice->invoiceno
                ];
                $res = $xero->createCreditNote(true, $payload->Contacts[0], $items, 'ID' . ($is_store == true ? $invoiceDetail->id : $input['edit_id']));
                if (!$res->ok()) {
                    throw new Exception('Failed to create credit note.');
                }
            } catch (\Throwable $th) {
                DB::rollback();
                report($th);
                
                $xero_has_err = true;
                Flash::error(__('invoices_details.something_went_wrong'));
            }
        }
        
        if (!$xero_has_err) {
            if ($is_store == true) {
                Flash::success(__('invoices_details.invoice_detail_saved_successfully'));
            } else {
                Flash::success(__('invoices_details.invoice_detail_updated_successfully'));
            }
            
            DB::commit();
        }
        
        Session::forget('invoice_detail_data');
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
        $input['edit_id'] = $id;
        
        Session::put('is_store', false);
        Session::put('invoice_detail_data', $input);

        $res = $this->upsertDetail(false, $request);
        if ($res != null) {
            return $res;
        }

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
    
    public function getprice($invoice_id,$product_id)
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
        }else{
            return response()->json(['status' => true, 'message' => 'Special Price found!', 'data' => $specialprice->price]);
        }

    }
}
