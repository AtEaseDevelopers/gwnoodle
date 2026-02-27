<?php

namespace App\Http\Controllers;

use App\DataTables\InvoicePaymentDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateInvoicePaymentRequest;
use App\Http\Requests\UpdateInvoicePaymentRequest;
use App\Repositories\InvoicePaymentRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Illuminate\Support\Facades\File;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use App\Models\Code;

class InvoicePaymentController extends AppBaseController
{
    /** @var InvoicePaymentRepository $invoicePaymentRepository*/
    private $invoicePaymentRepository;

    public function __construct(InvoicePaymentRepository $invoicePaymentRepo)
    {
        $this->invoicePaymentRepository = $invoicePaymentRepo;
    }

    /**
     * Display a listing of the InvoicePayment.
     *
     * @param InvoicePaymentDataTable $invoicePaymentDataTable
     *
     * @return Response
     */
    public function index(Request $req, InvoicePaymentDataTable $invoicePaymentDataTable)
    {
        return $invoicePaymentDataTable->render('invoice_payments.index');
    }

    /**
     * Show the form for creating a new InvoicePayment.
     *
     * @return Response
     */
    public function create()
    {
        return view('invoice_payments.create');
    }

    /**
     * Store a newly created InvoicePayment in storage.
     *
     * @param CreateInvoicePaymentRequest $request
     *
     * @return Response
     */
    public function store(CreateInvoicePaymentRequest $request)
    {
        $input = $request->all();
        
        if($input['type'] == 1 && !isset($input['status'])){
            $input['status'] = 1;
            $input['approve_by'] = Auth::user()->email;
            $input['approve_at'] = gmdate("Y-m-d H:i:s");
        }

        if($input['type'] == 2 && !isset($input['status'])){
            $input['status'] = 0;
            $input['approve_by'] = null;
            $input['approve_at'] = null;
        }

        if(isset($input['status'])){
            if($input['status'] == 1){
                $input['approve_by'] = Auth::user()->email;
                $input['approve_at'] = gmdate("Y-m-d H:i:s");
            }else{
                $input['approve_by'] = null;
                $input['approve_at'] = null;
            }
        }

        if($request->file('attachment') != null){
            $path = 'assets/img/invoicepayment/'.uniqid();
            if(!File::isDirectory($path)){
                File::makeDirectory($path, 0777, true, true);
            }
            $input['attachment'] = $request->file('attachment')->store($path);
        }

        $input['user_id'] = Auth::user()->id;
        $runningno = Code::where('code', 'prrunningnumber')->first();
        if ($runningno == null) {
            $runningno = Code::create([
                'code' => 'prrunningnumber',
                'description' => 'Payment running number',
                'value' => 0,
                'sequence' => 0
            ]);
        }
        $runningno->increment('value');
        $input['docno'] = 'PR' . sprintf('%05d', $runningno->value);

        if (isset($input['invoice_id']) && is_array($input['invoice_id']) && count($input['invoice_id']) > 0) {
            foreach ($input['invoice_id'] as $invoice_id) {
                if ($invoice_id) {
                    $invoice = Invoice::with('invoicedetail')->where('id', $invoice_id)->first();
                    if ($invoice && $invoice->invoicedetail) {
                        $input['invoice_id'] = $invoice_id;
                        $input['amount'] = $invoice->invoicedetail->sum("totalprice");
                        $this->invoicePaymentRepository->create($input);
                    }
                }
            }
        } else {
            if (isset($input['invoice_id']) && is_array($input['invoice_id'])) {
                unset($input['invoice_id']);
            }
            $this->invoicePaymentRepository->create($input);
        }

        Flash::success('Payment saved successfully.');

        return redirect(route('invoicePayments.index'));
    }

    /**
     * Display the specified InvoicePayment.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            Flash::error('Payment not found');

            return redirect(route('invoicePayments.index'));
        }

        return view('invoice_payments.show')->with('invoicePayment', $invoicePayment);
    }

    /**
     * Show the form for editing the specified InvoicePayment.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            Flash::error('Payment not found');

            return redirect(route('invoicePayments.index'));
        }

        // if($invoicePayment->status == 1){
        //     Flash::error('Cannot edit completed Payment!');

        //     return redirect(route('invoicePayments.index'));
        // }

        $invoices = Invoice::where('customer_id', $invoicePayment->customer_id)
            ->whereDoesntHave('invoicepayment', function ($query) {
                $query->where('status', 1);
            })
            ->orWhere("id", $invoicePayment->invoice_id ?? 0)
            ->get(['id', 'invoiceno', 'date']);

        $invoices->each(function ($invoice) {
            $invoice->total_amount = InvoiceDetail::where("invoice_id",$invoice->id)->sum('totalprice');
        });

        $multiplePayment = InvoicePayment::where("docno", $invoicePayment->docno)->whereNotNull("invoice_id")->get();
        $selectedInvoices = [];
        if($multiplePayment){
            $selectedInvoices = $multiplePayment->pluck("invoice_id")->toArray();
            if(count($selectedInvoices) > 0){
                $invoicePayment->amount = $multiplePayment->sum("amount");
            }
        }

        return view('invoice_payments.edit', compact('invoicePayment', 'selectedInvoices', 'invoices'));
    }

    /**
     * Update the specified InvoicePayment in storage.
     *
     * @param int $id
     * @param UpdateInvoicePaymentRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateInvoicePaymentRequest $request)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            Flash::error('Payment not found');

            return redirect(route('invoicePayments.index'));
        }

        $input = $request->all();

        $input['approve_by'] = Auth::user()->email;
        if (!isset($input['status'])) {
            $input['status'] = $invoicePayment->status;
        }
        if ((int) $input['status'] === 1) {
            $input['approve_at'] = gmdate("Y-m-d H:i:s");
        } else {
            $input['approve_at'] = null;
            $input['approve_by'] = null;
        }

        if($request->file('attachment') != null){
            $path = 'assets/img/invoicepayment/'.uniqid();
            if(!File::isDirectory($path)){
                File::makeDirectory($path, 0777, true, true);
            }
            $input['attachment'] = $request->file('attachment')->store($path);
        }
        
        $docno = $invoicePayment->docno;
        if (empty($docno)) {
            $runningno = Code::where('code', 'prrunningnumber')->first();
            if ($runningno == null) {
                $runningno = Code::create([
                    'code' => 'prrunningnumber',
                    'description' => 'Payment running number',
                    'value' => 0,
                    'sequence' => 0
                ]);
            }
            $runningno->increment('value');
            $docno = 'PR' . sprintf('%05d', $runningno->value);
        }

        if(isset($input['invoice_id']) && is_array($input['invoice_id']) && count($input['invoice_id']) > 0) {
            $inv_ids = $input['invoice_id'];
            $paymentsToCancel = InvoicePayment::where("docno", $docno)->whereNotIn('invoice_id', $inv_ids)->get();
            foreach($paymentsToCancel as $payment) {
                $payment->invoice_id = null;
                $payment->status = 2;
                $payment->save();
            }

            for ($i = 0; $i < count($inv_ids); $i++) {
                if(count($inv_ids) > 1) {
                    $amount = InvoiceDetail::where('invoice_id',$inv_ids[$i])->sum("totalprice");
                    $paymentRow = InvoicePayment::where('invoice_id', $inv_ids[$i])->where('docno', $docno)->first();
                    if(!$paymentRow) {
                        $paymentRow = InvoicePayment::where('docno', $docno)->whereNull('invoice_id')->first();
                    }

                    if($paymentRow) {
                        $paymentRow->amount = $amount;
                        $paymentRow->approve_by =  $input['approve_by'];
                        $paymentRow->approve_at =  $input['approve_at'];
                        $paymentRow->status = $input['status'];
                        $paymentRow->type = $input['type'];
                        $paymentRow->customer_id = $input['customer_id'];
                        $paymentRow->driver_id = $input['driver_id'] ?? $paymentRow->driver_id;
                        $paymentRow->invoice_id = $inv_ids[$i];
                        $paymentRow->attachment = $input['attachment'] ?? null;
                        $paymentRow->remark = $input['remark'];
                        $paymentRow->docno = $docno;
                        $paymentRow->save();
                    } else {
                        $invoicepayment_new = New InvoicePayment();
                        $invoicepayment_new->invoice_id = $inv_ids[$i];
                        $invoicepayment_new->type =  $input['type'];
                        $invoicepayment_new->customer_id = $input['customer_id'];
                        $invoicepayment_new->amount = $amount;
                        $invoicepayment_new->status = $input['status'];
                    $invoicepayment_new->driver_id = $input['driver_id'] ?? $invoicePayment->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->email;
                    $invoicepayment_new->approve_at = $input['approve_at'];
                    $invoicepayment_new->docno = $docno;
                    $invoicepayment_new->save();
                }
            } else {
                $invoicePaymentOriginal = InvoicePayment::find($id);
                $invoicePaymentOriginal->amount = $input['amount'];
                $invoicePaymentOriginal->approve_by =  $input['approve_by'];
                $invoicePaymentOriginal->approve_at =  $input['approve_at'];
                $invoicePaymentOriginal->status = $input['status'];
                $invoicePaymentOriginal->type = $input['type'];
                $invoicePaymentOriginal->customer_id = $input['customer_id'];
                $invoicePaymentOriginal->driver_id = $input['driver_id'] ?? $invoicePaymentOriginal->driver_id;
                $invoicePaymentOriginal->attachment = $input['attachment'] ?? null;
                $invoicePaymentOriginal->remark = $input['remark'];
                $invoicePaymentOriginal->invoice_id = $inv_ids[$i];
                $invoicePaymentOriginal->docno = $docno;
                $invoicePaymentOriginal->save();
            }
            }
        } else {
            if (isset($input['invoice_id']) && is_array($input['invoice_id'])) {
                unset($input['invoice_id']);
            }
            $invoicePayment = $this->invoicePaymentRepository->update($input, $id);
        }

        Flash::success('Payment updated successfully.');

        return redirect(route('invoicePayments.index'));
    }

    /**
     * Remove the specified InvoicePayment from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            Flash::error('Payment not found');

            return redirect(route('invoicePayments.index'));
        }

        if($invoicePayment->status == 1){
            Flash::error('Cannot delete completed Payment!');

            return redirect(route('invoicePayments.index'));
        }

        $this->invoicePaymentRepository->delete($id);

        Flash::success('Payment deleted successfully.');

        return redirect(route('invoicePayments.index'));
    }
    
    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        if($status == 1){
            $count = InvoicePayment::whereIn('id',$ids)->update(['status'=>$status,'approve_by'=>Auth::user()->email,'approve_at'=>gmdate("Y-m-d H:i:s")]);
        }else{
            $count = InvoicePayment::whereIn('id',$ids)->update(['status'=>$status,'approve_by'=>null,'approve_at'=>null]);
        }
    
        return $count;
    }
    
    public function getpayment($id)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            return response()->json(['status' => false, 'message' => 'Payment not found!']);
        }

        if($invoicePayment->status == 1){
            return response()->json(['status' => false, 'message' => 'Payment had been approved!']);
        }

        return response()->json(['status' => true, 'message' => 'Payment found!', 'data' => $invoicePayment]);
    
    }
    
    public function updatepayment(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $invoicePayment = $this->invoicePaymentRepository->find($id);

        if (empty($invoicePayment)) {
            Flash::error('Payment not found');
            return redirect(route('invoicePayments.index'));
        }

        if($invoicePayment->status != 0){
            Flash::error('Payment had been completed!');

            return redirect(route('invoicePayments.index'));
        }

        $input = $request->all();
        
        if($input['status'] == 1){
            $invoicePayment->approve_by = Auth::user()->email;
            $invoicePayment->approve_at = gmdate("Y-m-d H:i:s");
        }
        $invoicePayment->status = $input['status'];
        $invoicePayment->remark = $input['remark'];
        $invoicePayment->save();

        Flash::success('Payment udpated successfully.');

        return redirect(route('invoicePayments.index'));
    }
    
    public function getinvoice(Request $request)
    {
        $invoice_ids = $request->input('invoice_ids');
        if (is_array($invoice_ids)) {
            $invoices = Invoice::with('invoicedetail')->whereIn('id', $invoice_ids)->get();
        } else {
            $invoices = Invoice::with('invoicedetail')->where('id', $invoice_ids)->get();
        }

        if ($invoices->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Invoice not found!']);
        }

        return response()->json(['status' => true, 'message' => 'Invoice found!', 'data' => $invoices]);
    }

    public function getcustomerinvoice($id)
    {
        $invoices = Invoice::where('customer_id', $id)
            ->whereDoesntHave('invoicepayment', function ($query) {
                $query->where('status', 1);
            })
            ->get(['id', 'invoiceno', 'date']);

        $invoices->each(function ($invoice) {
            $invoice->total_amount = InvoiceDetail::where("invoice_id",$invoice->id)->sum('totalprice');
        });

        if ($invoices->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Invoices not found!']);
        }

        $invoiceData = $invoices->map(function ($invoice) {
            return [
                'id' => $invoice->id,
                'invoiceno' => $invoice->invoiceno,
                'date' => $invoice->date,
                'total_amount' => $invoice->total_amount
            ];
        });

        return response()->json(['status' => true, 'message' => 'Invoices found!', 'data' => $invoiceData]);
    }

    public function getallinvoice()
    {
        $invoice = Invoice::with('invoicedetail')->pluck('invoiceno','id')->toArray();

        if (empty($invoice)) {
            return response()->json(['status' => true, 'message' => 'Invoice not found!']);
        }

        return response()->json(['status' => true, 'message' => 'Invoice found!', 'data' => $invoice]);
    }

    public function print()
    {
        return view('invoice_payments.print');    
    }

    public function getReceiptViewPDF($id,$function)
    {
        $id = Crypt::decrypt($id);
        $invoice = InvoicePayment::where('id',$id)
        ->with('customer')
        ->first();

        if (empty($invoice)) {
            abort('404');
        }

        if (empty($invoice->docno)) {
            $multiplePayment = collect([$invoice]);
        } else {
            $multiplePayment = InvoicePayment::where("docno", $invoice->docno)->with('invoice')->get();
        }
        $invoice->amount = $multiplePayment->sum("amount");

        if(count($multiplePayment) > 0)
        {
            $items =  InvoiceDetail::whereIn("invoice_id",$multiplePayment->pluck("invoice_id"))
                ->with('product')
                ->with('invoice')
                ->get();

            $invoice->details  = collect($items)
                ->groupBy(function ($item) {
                    return $item->invoice->id;
                })
                ->map(function ($group) {
                    $invoice = $group->first()->invoice;
                    $sumTotal = $group->sum('totalprice');

                    return [
                        'invoiceno'   => $invoice->invoiceno,
                        'date'        => \Carbon\Carbon::parse($invoice->date)->format('d/m/Y'),
                        'finalprice'  => round($sumTotal, 2),
                    ];
                })
                ->values();
        }
        else
            $invoice->details = [];

        $min = 450;
        $each = 23;

    
        $creditQuery = "
            SELECT 
                (COALESCE(total_invoiced.totalprice, 0) - COALESCE(paymentsummary.amount, 0)) as credit
            FROM 
                customers
            LEFT JOIN (
                SELECT 
                    customer_id, 
                    SUM(invoice_details.totalprice) AS totalprice 
                FROM 
                    invoices 
                    LEFT JOIN invoice_details ON invoices.id = invoice_details.invoice_id 
                WHERE 
                    invoices.status = 1
                    AND invoices.updated_at <= ?
                GROUP BY 
                    customer_id
            ) AS total_invoiced ON customers.id = total_invoiced.customer_id 
            LEFT JOIN (
                SELECT 
                    customer_id, 
                    SUM(COALESCE(amount, 0)) AS amount 
                FROM 
                    invoice_payments 
                WHERE 
                    status = 1
                    AND updated_at <= ?
                GROUP BY 
                    customer_id
            ) AS paymentsummary ON customers.id = paymentsummary.customer_id 
            WHERE 
                customers.id = ?
            GROUP BY 
                customers.id, total_invoiced.totalprice, paymentsummary.amount
        ";

        $creditResult = DB::select($creditQuery, [
            $invoice->updated_at,
            $invoice->updated_at,
            $invoice->customer_id
        ]);
        
        $invoice->newcredit = !empty($creditResult) ? round($creditResult[0]->credit, 2) : 0;
        $invoice->customer->groupcompany = DB::table('companies')
        ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
        ->select('companies.*')
        ->first() ?? null;
        try{
            $pdf = Pdf::loadView('invoice_payments.print', array(
                'invoice' => $invoice
            ));

            if($function == 'download'){
                return $pdf->setPaper(array(0, 0, 300, $min), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->download('download.pdf');
            }elseif($function == 'view'){
                return $pdf->setPaper(array(0, 0, 300, $min), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])->stream('view.pdf');
            }
        }
        catch(Exception $e){
            abort(404);
        }

    }

}
