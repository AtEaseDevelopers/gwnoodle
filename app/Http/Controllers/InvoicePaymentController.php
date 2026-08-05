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
            $input['approve_by'] = Auth::user()->name;
            $input['approve_at'] = gmdate("Y-m-d H:i:s");
        }

        if($input['type'] == 2 && !isset($input['status'])){
            $input['status'] = 0;
            $input['approve_by'] = null;
            $input['approve_at'] = null;
        }

        if(isset($input['status'])){
            if($input['status'] == 1){
                $input['approve_by'] = Auth::user()->name;
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

        // One payment event = one AutoCount AR Payment. Tag every row created here
        // with the same batch id so they later sync as a single OR knocking off all
        // the invoices. Credit-term batches are held for a manual "Sync" click.
        $invoiceIdsForBatch = array_filter((array) ($input['invoice_id'] ?? []));
        $batchIsCredit = !empty($invoiceIdsForBatch) && Invoice::whereIn('id', $invoiceIdsForBatch)
            ->where('paymentterm', Invoice::PAYMENT_TERM_CREDIT)
            ->exists();
        // Auto-sync only a single-invoice, non-credit payment. A payment spanning
        // multiple invoices is held for a manual web "Sync" click (client: these are
        // almost always credit); it still becomes ONE OR knocking off all invoices.
        $isMultiInvoice = count($invoiceIdsForBatch) > 1;
        $input['payment_batch_id'] = (string) \Illuminate\Support\Str::uuid();
        $input['autocount_status'] = ($batchIsCredit || $isMultiInvoice)
            ? InvoicePayment::AC_HOLD : InvoicePayment::AC_PENDING;

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
        $invoice = Invoice::where('id', $input['invoice_id'])->first();
        $invoice->status = 1;
        $invoice->save();

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

        $invoices = Invoice::where('customer_id', $invoicePayment->customer_id)
            ->whereDoesntHave('invoicepayment', function ($query) {
                $query->where('status', 1);
            })
            ->orWhere("id", $invoicePayment->invoice_id ?? 0)
            ->get(['id', 'invoiceno', 'date']);

        $invoices->each(function ($invoice) {
            $invoice->total_amount = InvoiceDetail::where("invoice_id",$invoice->id)->sum('totalprice');
        });

        $selectedInvoices = [];
        if($invoicePayment->invoice_id) {
            $selectedInvoices = [$invoicePayment->invoice_id];
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

        $input['approve_by'] = Auth::user()->name;
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

        if(isset($input['invoice_id']) && is_array($input['invoice_id']) && count($input['invoice_id']) > 0) {
            $inv_ids = $input['invoice_id'];
            
            // For multiple invoices, we need to handle accordingly
            // Since docno is removed, we'll just update the current payment
            // and create additional payments if needed
            if(count($inv_ids) > 1) {
                // Update current payment with first invoice
                $firstId = array_shift($inv_ids);
                $invoicePayment->invoice_id = $firstId;
                $invoicePayment->amount = InvoiceDetail::where('invoice_id', $firstId)->sum("totalprice");
                $invoicePayment->save();

                // Create new payments for remaining invoices
                foreach ($inv_ids as $inv_id) {
                    $amount = InvoiceDetail::where('invoice_id', $inv_id)->sum("totalprice");
                    
                    $invoicepayment_new = new InvoicePayment();
                    $invoicepayment_new->invoice_id = $inv_id;
                    $invoicepayment_new->type = $input['type'];
                    $invoicepayment_new->customer_id = $input['customer_id'];
                    $invoicepayment_new->amount = $amount;
                    $invoicepayment_new->status = $input['status'];
                    $invoicepayment_new->driver_id = $input['driver_id'] ?? $invoicePayment->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->name;
                    $invoicepayment_new->approve_at = $input['approve_at'];
                    $invoicepayment_new->attachment = $input['attachment'] ?? null;
                    $invoicepayment_new->remark = $input['remark'];
                    $invoicepayment_new->save();
                }
            } else {
                // Single invoice
                $invoicePayment->amount = $input['amount'];
                $invoicePayment->approve_by = $input['approve_by'];
                $invoicePayment->approve_at = $input['approve_at'];
                $invoicePayment->status = $input['status'];
                $invoicePayment->type = $input['type'];
                $invoicePayment->customer_id = $input['customer_id'];
                $invoicePayment->driver_id = $input['driver_id'] ?? $invoicePayment->driver_id;
                $invoicePayment->attachment = $input['attachment'] ?? null;
                $invoicePayment->remark = $input['remark'];
                $invoicePayment->invoice_id = $inv_ids[0];
                $invoicePayment->save();
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
    
    /**
     * Manually queue a payment (and its whole batch) for AutoCount AR Payment sync.
     *
     * Used for credit-term payments (created on 'hold' and never auto-synced) and
     * to re-sync a payment that already synced ('success'/'failed'). Releasing the
     * batch to 'pending' is all that is needed - the plugin polls /api/payment/pending
     * and, because payment_no/doc_id are retained, edits the existing OR instead of
     * creating a duplicate.
     */
    public function syncAutocount($id)
    {
        // Accept the encrypted id used elsewhere in this controller, or a raw id.
        try { $id = Crypt::decrypt($id); } catch (\Throwable $e) { /* already raw */ }

        $payment = $this->invoicePaymentRepository->find($id);
        if (empty($payment)) {
            return response()->json(['status' => false, 'message' => 'Payment not found!'], 404);
        }
        if ((int) $payment->status !== 1) {
            return response()->json(['status' => false, 'message' => 'Only approved payments can be synced to AutoCount.'], 422);
        }
        if (empty($payment->payment_batch_id)) {
            return response()->json(['status' => false, 'message' => 'This payment has no batch and cannot be synced.'], 422);
        }

        $count = InvoicePayment::where('payment_batch_id', $payment->payment_batch_id)
            ->update([
                'autocount_status'       => InvoicePayment::AC_PENDING,
                'autocount_auto_retried' => false,
            ]);

        return response()->json([
            'status'  => true,
            'message' => 'Payment queued for AutoCount sync.',
            'count'   => $count,
        ]);
    }

    /**
     * Bulk-queue selected payments (and their whole batches) for AutoCount sync.
     * Mirrors the invoices listing's "Submit Autocount Invoice" toolbar action.
     * A payment event may span several rows (one batch), so releasing any selected
     * row releases its whole batch. Only approved (status=1) rows are queued.
     */
    public function massSyncAutocount(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['status' => false, 'message' => 'Please select at least one payment.'], 422);
        }

        $batchIds = InvoicePayment::whereIn('id', $ids)
            ->whereNotNull('payment_batch_id')
            ->pluck('payment_batch_id')->unique()->values();

        if ($batchIds->isEmpty()) {
            return response()->json(['status' => false, 'message' => 'Selected payment(s) have no batch to sync.'], 422);
        }

        $count = InvoicePayment::whereIn('payment_batch_id', $batchIds)
            ->where('status', 1)
            ->update([
                'autocount_status'       => InvoicePayment::AC_PENDING,
                'autocount_auto_retried' => false,
            ]);

        return response()->json([
            'status'  => true,
            'message' => $count . ' payment(s) queued for AutoCount sync.',
            'count'   => $count,
        ]);
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        if($status == 1){
            $count = InvoicePayment::whereIn('id',$ids)->update(['status'=>$status,'approve_by'=>Auth::user()->name,'approve_at'=>gmdate("Y-m-d H:i:s")]);
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
            $invoicePayment->approve_by = Auth::user()->name;
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

    private function calculateCustomerCredit($customerId, $asOfDate)
    {
        try {
            // Get total invoiced amount for credit invoices
            $totalInvoiced = Invoice::where('invoices.customer_id', $customerId)
                ->where('invoices.paymentterm', Invoice::PAYMENT_TERM_CREDIT)
                ->where('invoices.updated_at', '<=', $asOfDate)
                ->join('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
                ->selectRaw('COALESCE(SUM(invoice_details.totalprice), 0) as total')
                ->value('total');

            // Get total paid amount for credit invoices (only completed payments)
            $totalPaid = InvoicePayment::where('customer_id', $customerId)
                ->where('status', 1) // Completed payment
                ->where('approve_at', '<=', $asOfDate)
                ->whereHas('invoice', function($query) {
                    $query->where('paymentterm', Invoice::PAYMENT_TERM_CREDIT)
                        ->where('status', Invoice::STATUS_COMPLETED);
                })
                ->sum('amount') ?? 0;

            $outstandingBalance = $totalInvoiced - $totalPaid;

            return [
                'totalprice' => round($totalInvoiced, 2),
                'paid' => round($totalPaid, 2),
                'credit' => round($outstandingBalance, 2)
            ];
            
        } catch (\Exception $e) {
            Log::error('Credit calculation failed: ' . $e->getMessage());
            return [
                'totalprice' => 0,
                'paid' => 0,
                'credit' => 0
            ];
        }
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

        $invoice->amount = $invoice->amount;

        if($invoice->invoice_id)
        {
            $items = InvoiceDetail::where("invoice_id", $invoice->invoice_id)
                ->with('product')
                ->with('invoice')
                ->get();

            $invoice->details = collect([$items->first()->invoice])->map(function ($inv) use ($items) {
                return [
                    'invoiceno'   => $inv->invoiceno,
                    'date'        => \Carbon\Carbon::parse($inv->date)->format('d/m/Y'),
                    'finalprice'  => round($items->sum('totalprice'), 3),
                ];
            })->values();
        }
        else
            $invoice->details = [];

        $min = 450;
        $each = 23;

        $creditData = $this->calculateCustomerCredit(
                $invoice->customer_id, 
                $invoice->updated_at
            );
        
        $invoice->newcredit = round($creditData['credit'] ?? 0, 2);
        $invoice->customer->groupcompany = DB::table('companies')
        ->where('companies.group_id', explode(',', $invoice->customer->group)[0] ?? '')
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