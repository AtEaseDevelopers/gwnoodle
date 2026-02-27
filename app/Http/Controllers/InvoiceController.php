<?php

namespace App\Http\Controllers;

use App\DataTables\InvoiceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Repositories\InvoiceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Trip;
use App\Models\Product;
use App\Models\Task;
use App\Models\Code;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Session;
use Exception;

class InvoiceController extends AppBaseController
{
    /** @var InvoiceRepository $invoiceRepository*/
    private $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepo)
    {
        $this->invoiceRepository = $invoiceRepo;
    }

    /**
     * Display a listing of the Invoice.
     *
     * @param InvoiceDataTable $invoiceDataTable
     *
     * @return Response
     */
    public function index(Request $request, InvoiceDataTable $invoiceDataTable)
    {
        $this->syncDetailToXero($request);

        return $invoiceDataTable->render('invoices.index');
    }

    /**
     * Show the form for creating a new Invoice.
     *
     * @return Response
     */
    public function create()
    {
        return view('invoices.create');
    }

    /**
     * Store a newly created Invoice in storage.
     *
     * @param CreateInvoiceRequest $request
     *
     * @return Response
     */
    public function store(CreateInvoiceRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if($input['invoiceno'] == null){
            Code::where('code','invoicerunningnumber')->first()->increment('value');
            $input['invoiceno'] = 'INV'.sprintf('%07d',Code::where('code','invoicerunningnumber')->first()->value);
        }

        $invoice = $this->invoiceRepository->create($input);

        if($input['driver_id'] != ''){
            $trip = Trip::where('driver_id',$input['driver_id'])->orderBy('id','desc')->first();
            if(!empty($trip)){
                //check user start trip
                if($trip->type == 1){
                    $exttask = Task::where('driver_id',$input['driver_id'])->where('status',0);
                    if($exttask->count() != 0){
                        $sequence = $exttask->orderby('sequence','asc')->get()->first()->sequence;
                        $exttask->increment('sequence');
                    }else{
                        $sequence = 1;
                    }
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $invoice->driver_id;
                    $task->customer_id = $invoice->customer_id;
                    $task->sequence = $sequence;
                    $task->invoice_id = $invoice->id;
                    $task->status = 0;
                    $task->save();
                    Flash::success('Invoice saved and assigned to driver successfully.');
                }
            }else{
                Flash::success('Invoice saved successfully.');
            }
        }else{
            Flash::success('Invoice saved successfully.');
        }

        if($input['method'] == 1){
            return redirect(route('invoices.index'));
        }else{
            return redirect(route('invoices.show',encrypt($invoice->id)));
        }

    }

    /**
     * Display the specified Invoice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }

        $invoicedetails = InvoiceDetail::with('product')->where('invoice_id',$id)->get()->toArray();

        return view('invoices.show')->with('invoice', $invoice)->with('invoicedetails', $invoicedetails)->with('id',$id);
    }

    /**
     * Show the form for editing the specified Invoice.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }

        return view('invoices.edit')->with('invoice', $invoice);
    }

    /**
     * Update the specified Invoice in storage.
     *
     * @param int $id
     * @param UpdateInvoiceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInvoiceRequest $request)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }
    
        $old_payment = $invoice['paymentterm'];
        
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if($input['invoiceno'] == null){
            Code::where('code','invoicerunningnumber')->first()->increment('value');
            $input['invoiceno'] = 'INV'.sprintf('%07d',Code::where('code','invoicerunningnumber')->first()->value);
        }

        $invoice = $this->invoiceRepository->update($input, $id);

         if($old_payment != $input['paymentterm'])
        {
            $invoicePayment = InvoicePayment::where('invoice_id', $id)->first();

            if($old_payment == "1")
            {
                if($invoicePayment)
                {
                    // Cancel Invoice Payment
                    $invoicePayment->status = 2;
                    $invoicePayment->approve_by = null;
                    $invoicePayment->approve_at = null;
                    $invoicePayment->save();
                }
            }
            else
            {
                if($invoicePayment)
                {
                    $invoicePayment->status = 1;
                    $invoicePayment->approve_by = Auth::user()->email;
                    $invoicePayment->approve_at = date('Y-m-d H:i:s');
                    $invoicePayment->save();
                }
                else
                {
                    $totalAmount = InvoiceDetail::where('invoice_id', $id)->sum('totalprice');

                    $invoicepayment_new = New InvoicePayment();
                    $invoicepayment_new->invoice_id = $id;
                    $invoicepayment_new->type = 1;
                    $invoicepayment_new->customer_id = $invoice->customer_id;
                    $invoicepayment_new->amount = $totalAmount;
                    $invoicepayment_new->status = $invoice->status;
                    $invoicepayment_new->driver_id = $invoice->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->email;
                    $invoicepayment_new->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment_new->save();
                }
            }
        }
        else
        {
            if($input['paymentterm'] == 1)
            {
                $totalAmount = InvoiceDetail::where('invoice_id', $id)->sum('totalprice');

                $invoicePayment = InvoicePayment::where('invoice_id', $id)->first();

                if(!$invoicePayment)
                {
                    $invoicepayment_new = New InvoicePayment();
                    $invoicepayment_new->invoice_id = $id;
                    $invoicepayment_new->type = 1;
                    $invoicepayment_new->customer_id = $invoice->customer_id;
                    $invoicepayment_new->amount = $totalAmount;
                    $invoicepayment_new->status = $invoice->status;
                    $invoicepayment_new->driver_id = $invoice->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->email;
                    $invoicepayment_new->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment_new->save();
                }
                else
                {
                    $invoicePayment->status = 1;
                    $invoicePayment->amount = $totalAmount;
                    $invoicePayment->save();
                }
            }
        }


        if($input['driver_id'] != ''){
            $task = Task::where('invoice_id',$invoice->id)->where('status',0)->first();
            if(!empty($task)){
                //Task found
                if($task->driver_id != $invoice->driver_id){
                    //if driver changed
                    $task_status = $task->status;
                    if($task_status == 0){
                        //task is new
                        $task->status = 9;
                        $task->save();

                        $trip = Trip::where('driver_id',$input['driver_id'])->orderBy('id','desc')->first();
                        if(!empty($trip)){
                            //check user start trip
                            if($trip->type == 1){
                                $exttask = Task::where('driver_id',$input['driver_id'])->where('status',0);
                                if($exttask->count() != 0){
                                    $sequence = $exttask->orderby('sequence','asc')->get()->first()->sequence;
                                    $exttask->increment('sequence');
                                }else{
                                    $sequence = 1;
                                }
                                $task = new Task();
                                $task->date = date("Y-m-d");
                                $task->driver_id = $invoice->driver_id;
                                $task->customer_id = $invoice->customer_id;
                                $task->sequence = $sequence;
                                $task->invoice_id = $invoice->id;
                                $task->status = 0;
                                $task->save();
                                Flash::success('Invoice updated and assigned to another driver successfully.');
                            }
                        }else{
                            Flash::success('Invoice updated successfully.');
                        }

                    }
                    if($task_status == 9){
                        //task is cancelled
                        $trip = Trip::where('driver_id',$input['driver_id'])->orderBy('id','desc')->first();
                        if(!empty($trip)){
                            //check user start trip
                            if($trip->type == 1){
                                $exttask = Task::where('driver_id',$input['driver_id'])->where('status',0);
                                if($exttask->count() != 0){
                                    $sequence = $exttask->orderby('sequence','asc')->get()->first()->sequence;
                                    $exttask->increment('sequence');
                                }else{
                                    $sequence = 1;
                                }
                                $task = new Task();
                                $task->date = date("Y-m-d");
                                $task->driver_id = $invoice->driver_id;
                                $task->customer_id = $invoice->customer_id;
                                $task->sequence = $sequence;
                                $task->invoice_id = $invoice->id;
                                $task->status = 0;
                                $task->save();
                                Flash::success('Invoice updated and assigned to another driver successfully.');
                            }
                        }else{
                            Flash::success('Invoice updated successfully.');
                        }

                    }
                    if($task_status == 1){
                        Flash::success('Invoice updated successfully.');
                    }
                    if($task_status == 8){
                        Flash::success('Invoice updated successfully.');
                    }
                }
            }else{
                //Task not found
                $trip = Trip::where('driver_id',$input['driver_id'])->orderBy('id','desc')->first();
                if(!empty($trip)){
                    //check user start trip
                    if($trip->type == 1){
                        $exttask = Task::where('driver_id',$input['driver_id'])->where('status',0);
                        if($exttask->count() != 0){
                            $sequence = $exttask->orderby('sequence','asc')->get()->first()->sequence;
                            $exttask->increment('sequence');
                        }else{
                            $sequence = 1;
                        }
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $invoice->driver_id;
                        $task->customer_id = $invoice->customer_id;
                        $task->sequence = $sequence;
                        $task->invoice_id = $invoice->id;
                        $task->status = 0;
                        $task->save();
                        Flash::success('Invoice updated and assigned to driver successfully.');
                    }
                }else{
                    Flash::success('Invoice updated successfully.');
                }
            }
        }else{
            Flash::success('Invoice updated successfully.');
        }

        if($input['method'] == 1){
            return redirect(route('invoices.index'));
        }else{
            return redirect(route('invoices.show',encrypt($invoice->id)));
        }
    }

    /**
     * Remove the specified Invoice from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }

        $task = Task::where('invoice_id',$invoice->id)->first();
        if(!empty($task)){
            if ($task->status != 0) {
                Flash::error('Invoice had been processed by driver');

                return redirect(route('invoices.index'));
            }

            $task->delete();
        }

        $this->invoiceRepository->delete($id);
        $invoicedetail = Invoicedetail::where('invoice_id',$invoice->id)->delete();

        Flash::success('Invoice deleted successfully.');

        return redirect(route('invoices.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $invoice = $this->invoiceRepository->find($id);

            $task = Task::where('invoice_id',$invoice->id)->first();
            if(!empty($task)){
                if ($task->status != 0) {
                    continue;
                }

                $task->delete();
            }
            $invoicedetail = Invoicedetail::where('invoice_id',$invoice->id)->delete();

            $count = $count + invoice::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = invoice::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function getcustomer($id)
    {
        $customer = Customer::where('id',$id)->first();

        if (empty($customer)) {
            return response()->json(['status' => false, 'message' => 'Customer not found!']);
        }

        return response()->json(['status' => true, 'message' => 'Customer found!', 'data' => $customer]);
    }

    public function detail(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }

        return view('invoices.detail')->with('id', $id);
    }

    public function adddetail($id , Request $request)
    {
        $id = Crypt::decrypt($id);

        $input = $request->all();

        $driver = $this->invoiceRepository->find($id);

        if (empty($driver)) {
            Flash::error('Invoice not found');

            return redirect(route('invoices.index'));
        }
        
        $input['invoice_id'] = $id;
        Session::put('invoice_detail_data', $input);

        $res = $this->syncDetailToXero($request);
        if ($res != null) {
            return $res;
        }
        
        return redirect(route('invoices.show',encrypt($id)));
    }
    
    private function syncDetailToXero(Request $request) {
        $input = Session::get('invoice_detail_data');
        if ($input == null) {
            return null;
        }
        
        $invoice = Invoice::where('id', $input['invoice_id'])->first();

        // DB::beginTransaction();
        
        $invoicedetail = new InvoiceDetail();
        $invoicedetail->invoice_id  = $input['invoice_id'];
        $invoicedetail->product_id = $input['product_id'];
        $invoicedetail->quantity = $input['quantity'];
        $invoicedetail->price = $input['price'];
        $invoicedetail->totalprice = $input['quantity'] * $input['price'];
        $invoicedetail->remark = $input['remark'];
        $invoicedetail->save();
        

        if($invoice->paymentterm == 1)
        {
            // Check invoice payment if cash 
            $id = $input['invoice_id'];
            $invoicePayment = InvoicePayment::where('invoice_id', $id)->first();
            
            $totalAmount = InvoiceDetail::where('invoice_id', $id)->sum('totalprice');

            if(!$invoicePayment)
            {
                $invoicepayment_new = New InvoicePayment();
                $invoicepayment_new->invoice_id = $id;
                $invoicepayment_new->type = 1;
                $invoicepayment_new->customer_id = $invoice->customer_id;
                $invoicepayment_new->amount = $totalAmount;
                $invoicepayment_new->status = $invoice->status;
                $invoicepayment_new->driver_id = $invoice->driver_id;
                $invoicepayment_new->approve_by = Auth::user()->email;
                $invoicepayment_new->approve_at = date('Y-m-d H:i:s');
                $invoicepayment_new->save();
            }
            else
            {
                $invoicePayment->status = 1;
                $invoicePayment->amount = $totalAmount;
                $invoicePayment->save();
            }
        }


        
        // $xero_has_err = false;
        // try {
        //     $redirect_uri = config('app.url') . '/invoices';
        //     $xero = new XeroController($redirect_uri);

        //     // Get Xero's access token
        //     if ($request->has('code')) {
        //         $res = $xero->getToken($request->code);
        //         if (!$res->ok()) {
        //             throw new Exception('Failed to get xero access token.');
        //         }
        //     }
        //     // Xero auth
        //     $res = $xero->auth();
        //     if ($res !== true) {
        //         return $res;
        //     }
        //     // Get contact
        //     $customer_name = Customer::where('id', $invoice->customer_id)->value('company');
            
        //     $res = $xero->getContact($customer_name); // Get contact
        //     $payload = $res->object();

        //     if (!$res->ok()) {
        //         throw new Exception('Failed to get xero contact.');
        //     } elseif ($res->ok() && isset($payload->Contacts) && count($payload->Contacts) <= 0) { // Create contact in Xero
        //         $res = $xero->createContact($customer_name);
        //         if (!$res->ok()) {
        //             throw new Exception('Failed to create contact for ' . $customer_name);
        //         }
        //         $payload = $res->object();
        //     }
        //     // Create credit note
        //     $items = [
        //         'Quantity' => $input['quantity'],
        //         'UnitAmount' => $input['price'], 
        //         'Description' => $input['remark'] ?? $invoice->invoiceno
        //     ];
        //     $res = $xero->createCreditNote(true, $payload->Contacts[0], $items, 'ID' . $invoicedetail->id);
        //     if (!$res->ok()) {
        //         throw new Exception('Failed to create credit note.');
        //     }
            
        //     DB::commit();
        // } catch (\Throwable $th) {
        //     DB::rollback();
        //     report($th);
            
        //     $xero_has_err = true;
        //     Flash::error('Something went wrong. Please contact administator.');
        // }

        // if (!$xero_has_err) {
            Flash::success('Invoice Detail saved successfully.');
        // }
        
        Session::forget('invoice_detail_data');
    }

    public function deletedetail($id)
    {
        $id = Crypt::decrypt($id);

        $invoicedetail = InvoiceDetail::where('id',$id)->first();

        if (empty($invoicedetail)) {
            Flash::error('Invoice Detail not found');

            return redirect()->back();
        }

        $invoicedetail->delete($id);

        Flash::success('Invoice Detail deleted successfully.');

        return redirect(route('invoices.show',encrypt($invoicedetail->invoice_id)));
    }

    public function print()
    {
        return view('invoices.print');
    }

    public function getInvoiceViewPDF($id,$function)
    {
        $id = Crypt::decrypt($id);
        $invoice = Invoice::where('id',$id)
        ->with('customer')
        ->with('driver')
        ->with('invoicedetail.product')
        ->first();

        if (empty($invoice)) {
            abort('404');
        }

        $min = 450;
        $each = 23;
        $height = (count($invoice['invoicedetail']) * $each) + $min;

        $invoice->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');')[0]->credit,2);
        $invoice->customer->groupcompany = DB::table('companies')
        ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
        ->select('companies.*')
        ->first() ?? null;
        try{
            $pdf = Pdf::loadView('invoices.print', array(
                'invoice' => $invoice
            ));

            if($function == 'download'){
                return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->download('download.pdf');
            }elseif($function == 'view'){
                return $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->stream('view.pdf');
            }
        }
        catch(Exception $e){
            abort(404);
        }

    }

    public function syncXero(Request $req)
    {
        try {
            $redirect_uri = config('app.url') . '/invoices/sync-xero';
            $xero = new XeroController($redirect_uri);

            if ($req->has('ids')) {
                $ids = explode(',', $req->ids);
                Session::put('ids_to_sync_xero', $ids);
            }
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
            // Sync customers
            $ids = Session::get('ids_to_sync_xero');
            $records = invoice::whereIn('id', $ids)->get();
            
            $invoice_data = [];
            for ($i = 0; $i < count($records); $i++) {
                if (!isset($invoice_data[$records[$i]['invoiceno']])) {
                    $invoice_data[$records[$i]['invoiceno']] = [
                        'invoice_date' => $records[$i]['date'],
                        'contact_name' => Customer::where('id', $records[$i]->customer_id)->value('company'),
                        'line_items' => [],
                        'currency_code' => strtoupper('myr'),
                    ];
                }
                // Prepare line items
                $invoice_details = InvoiceDetail::where('invoice_id', $records[$i]->id)->get();
                
                for ($j = 0; $j < count($invoice_details); $j++) {
                    $invoice_data[$records[$i]['invoiceno']]['line_items'][] = [
                        'ItemCode' => Product::where('id', $invoice_details[$j]->product_id)->value('code'),
                        'Description' => $invoice_details[$j]->remark,
                        'Quantity' => $invoice_details[$j]->quantity,
                        'UnitAmount' => $invoice_details[$j]->price,
                    ];   
                }
            }
            // Insert into Xero
            foreach ($invoice_data as $sku => $data) {
                $res = $xero->getContact($data['contact_name']); // Get contact
                $payload = $res->object();

                if (!$res->ok()) {
                    throw new Exception('Failed to get xero contact.');
                } elseif ($res->ok() && isset($payload->Contacts) && count($payload->Contacts) <= 0) { // Create contact in Xero
                    $res = $xero->createContact($data['contact_name']);
                    if (!$res->ok()) {
                        throw new Exception('Failed to create contact for ' . $data['contact_name']);
                    }
                    $payload = $res->object();
                }

                // Create invoice
                $res = $xero->createInvoice($payload->Contacts[0], $data);
                if (!$res->ok()) {
                    if ($res->object()->ErrorNumber == 10) { // Invoice alrdy exists
                        Invoice::where('invoiceno', $sku)->update([
                            'xero_status' => Invoice::STATUS_VOIDED
                        ]);
                        throw new Exception('The invoice is voided. No sync is allowed.', 1);
                    }
                    throw new Exception('Failed to create xero invoice.');
                }
                // Update status
                Invoice::where('invoiceno', $sku)->update([
                    'xero_status' => Invoice::STATUS_SYNCED_TO_XERO
                ]);
            }
            
            Flash::success('Invoices synced to Xero.');
            return redirect(route('invoices.index'));
        } catch (\Throwable $th) {
            report($th);

            Flash::error('Something went wrong. Please contact administator.');
            return redirect(route('invoices.index'));
        }
    }
}