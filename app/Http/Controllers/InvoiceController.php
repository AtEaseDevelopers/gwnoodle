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
use App\Models\ProductBatch;
use App\Models\InventoryTransaction;
use App\Models\InvoiceDetail;
use App\Models\InvoicePayment;
use App\Models\Trip;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\InventoryBalance;
use App\Models\WarehouseInventoryBalance;
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
        $nextInvoiceNumber = Invoice::getNextInvoiceNumber();

        return view('invoices.create', compact('nextInvoiceNumber'));
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
        // Invoices are always created as Completed - Cancelled is only ever
        // reached afterward via massupdatestatus(), never at creation time.
        $input['status'] = Invoice::STATUS_COMPLETED;
        $input['created_by'] = Auth::id();

        $requestedInvoiceNo = (!empty($input['invoiceno']) && $input['invoiceno'] !== 'SYSTEM GENERATED IF BLANK')
            ? $input['invoiceno']
            : null;

        $invoice = null;
        $attempts = 0;
        $useRequestedInvoiceNo = true;

        while (!$invoice) {
            $attempts++;

            try {
                $invoice = DB::transaction(function () use (&$input, $requestedInvoiceNo, $useRequestedInvoiceNo) {
                    if ($useRequestedInvoiceNo && $requestedInvoiceNo && !Invoice::invoiceNumberExists($requestedInvoiceNo)) {
                        $input['invoiceno'] = $requestedInvoiceNo;
                    } else {
                        $input['invoiceno'] = Invoice::generateInvoiceNumber();
                    }

                    return $this->invoiceRepository->create($input);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                if ($attempts >= 5 || !Invoice::isDuplicateInvoiceNumberException($e)) {
                    throw $e;
                }
                // Duplicate invoiceno race - retry with a freshly generated number
                $useRequestedInvoiceNo = false;
            }
        }

        Flash::success('Invoice created successfully.');
        

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

        $invoicedetails = InvoiceDetail::with('product')->with('batch')->where('invoice_id',$id)->get()->toArray();

        $isDriverInvoice = !empty($invoice->trip_uuid);
        $needsReturnWarehouseSelection = $isDriverInvoice && !($invoice->driver && $invoice->driver->trip_id);

        return view('invoices.show')
            ->with('invoice', $invoice)
            ->with('invoicedetails', $invoicedetails)
            ->with('id',$id)
            ->with('isDriverInvoice', $isDriverInvoice)
            ->with('needsReturnWarehouseSelection', $needsReturnWarehouseSelection);
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

        if (strtolower($invoice->autocount_status ?? '') === 'completed') {
            Flash::error('This invoice has been submitted to Autocount and can no longer be edited.');

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

        if (strtolower($invoice->autocount_status ?? '') === 'completed') {
            Flash::error('This invoice has been submitted to Autocount and can no longer be edited.');

            return redirect(route('invoices.index'));
        }

        $old_payment = $invoice['paymentterm'];
        $oldStatus = (int) $invoice->status;

        $input = $request->all();

        $input['date'] = date_create($input['date']);
        if($input['invoiceno'] == null){
            Code::where('code','invoicerunningnumber')->first()->increment('value');
            $input['invoiceno'] = 'INV'.sprintf('%07d',Code::where('code','invoicerunningnumber')->first()->value);
        }

        $newStatus = isset($input['status']) ? (int) $input['status'] : $oldStatus;
        $statusChangeNeedsStockAdjustment = $newStatus !== $oldStatus
            && in_array(Invoice::STATUS_CANCELLED, [$newStatus, $oldStatus], true);

        if ($statusChangeNeedsStockAdjustment) {
            DB::beginTransaction();
            try {
                if ($newStatus === Invoice::STATUS_CANCELLED) {
                    $this->returnInvoiceStock($invoice);
                } else {
                    $this->deductInvoiceStock($invoice);
                }

                $invoice = $this->invoiceRepository->update($input, $id);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Flash::error('Could not update invoice status: ' . $e->getMessage());

                return redirect()->back()->withInput();
            }
        } else {
            $invoice = $this->invoiceRepository->update($input, $id);
        }

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
                    $invoicepayment_new->status = 1; // Cash payment - always created as Completed
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
                    $invoicepayment_new->status = 1; // Cash payment - always created as Completed
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

        // Runs last, after the legacy payment-term sync logic above (which
        // unconditionally sets a cash invoice's payment back to status 1)
        // so a Cancelled/un-Cancelled status change always has the final say
        // on the payment's status.
        if ($statusChangeNeedsStockAdjustment) {
            $this->syncInvoicePaymentStatus($invoice, $newStatus);
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
        $newStatus = (int) $data['status'];

        if (!in_array($newStatus, [Invoice::STATUS_COMPLETED, Invoice::STATUS_CANCELLED], true)) {
            return response()->json(['message' => 'Invalid status'], 422);
        }

        $invoices = Invoice::whereIn('id', $ids)
            ->with(['invoicedetail.product', 'driver'])
            ->get();

        $updatedCount = 0;
        $errors = [];

        foreach ($invoices as $invoice) {
            $oldStatus = (int) $invoice->status;

            if ($oldStatus === $newStatus) {
                continue; // already in the target status - nothing to do
            }

            if (strtolower($invoice->autocount_status ?? '') === 'completed') {
                $errors[] = "Invoice {$invoice->invoiceno} already submitted to Autocount, skipped";
                continue;
            }

            DB::beginTransaction();
            try {
                if ($newStatus === Invoice::STATUS_CANCELLED) {
                    $this->returnInvoiceStock($invoice);
                } else {
                    $this->deductInvoiceStock($invoice);
                }

                $invoice->status = $newStatus;
                $invoice->save();

                $this->syncInvoicePaymentStatus($invoice, $newStatus);

                DB::commit();
                $updatedCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $errors[] = "Invoice {$invoice->invoiceno}: " . $e->getMessage();
            }
        }

        return response()->json([
            'updated_count' => $updatedCount,
            'errors' => $errors,
        ]);
    }

    /**
     * Keep the invoice's InvoicePayment record in sync with a status change.
     * Cancelling marks the payment Cancelled (status 2) and clears its
     * approval, mirroring the existing "Cancel Invoice Payment" behavior in
     * update() when payment term changes away from cash. Un-cancelling only
     * has a well-defined restore target for cash payments (back to
     * Completed, status 1) - there's no more "New"/pending status to fall
     * back to for non-cash payments, and no record of what it was before
     * cancellation, so those are left untouched.
     */
    private function syncInvoicePaymentStatus(Invoice $invoice, int $newInvoiceStatus): void
    {
        $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
        if (!$invoicePayment) {
            return;
        }

        if ($newInvoiceStatus === Invoice::STATUS_CANCELLED) {
            $invoicePayment->status = 2;
            $invoicePayment->approve_by = null;
            $invoicePayment->approve_at = null;
        } elseif ((int) $invoice->paymentterm === Invoice::PAYMENT_TERM_CASH) {
            $invoicePayment->status = 1;
            $invoicePayment->approve_by = Auth::user()->email;
            $invoicePayment->approve_at = now();
        } else {
            return;
        }

        $invoicePayment->save();
    }

    /**
     * Cancelling an invoice: return stock for every line item that's
     * currently holding inventory.
     *
     * Warehouse-sourced items (warehouse_id set) are ALWAYS treated as
     * deducted, regardless of deducted_from_inventory - that column is
     * tinyint NOT NULL DEFAULT 0 and InvoiceDetailController::upsertDetail()
     * (the normal admin create/edit path for these) never sets it, so it
     * reads false for essentially every warehouse-sourced row even though
     * stock genuinely was taken (insufficient warehouse stock always throws
     * there rather than silently skipping - there's no "not deducted" state
     * to represent for this path).
     *
     * Driver/lorry-sourced items (warehouse_id null) DO rely on the flag,
     * since bulkCreateInvoice's itemsToIgnore is a real "never deducted"
     * case that must not be returned.
     */
    private function returnInvoiceStock(Invoice $invoice): void
    {
        foreach ($invoice->invoicedetail as $detail) {
            if ($detail->quantity <= 0) {
                continue;
            }

            if (!$detail->warehouse_id && $detail->deducted_from_inventory === false) {
                continue;
            }

            $this->adjustInvoiceDetailStock($invoice, $detail, 'return');

            $detail->deducted_from_inventory = false;
            $detail->save();
        }
    }

    /**
     * Un-cancelling an invoice (back to Completed): re-deduct stock for
     * every line item that isn't currently holding inventory. Mirrors
     * returnInvoiceStock() - warehouse items always re-deducted, lorry items
     * gated by deducted_from_inventory.
     */
    private function deductInvoiceStock(Invoice $invoice): void
    {
        foreach ($invoice->invoicedetail as $detail) {
            if ($detail->quantity <= 0) {
                continue;
            }

            if (!$detail->warehouse_id && $detail->deducted_from_inventory !== false) {
                continue;
            }

            $this->adjustInvoiceDetailStock($invoice, $detail, 'deduct');

            $detail->deducted_from_inventory = true;
            $detail->save();
        }
    }

    /**
     * Shared warehouse/lorry stock adjustment for a single invoice detail
     * line, used by both directions of the cancel/un-cancel status flow.
     * Mirrors the same warehouse-vs-lorry branching as updatedetail().
     */
    private function adjustInvoiceDetailStock(Invoice $invoice, InvoiceDetail $detail, string $direction): void
    {
        $qty = $detail->quantity;
        $productName = $detail->product->name ?? $detail->product_id;
        $productBatch = ProductBatch::find($detail->product_batch_id);

        if ($detail->warehouse_id) {
            $warehouseBalance = WarehouseInventoryBalance::where('warehouse_id', $detail->warehouse_id)
                ->where('batch_id', $detail->product_batch_id)
                ->first();

            if ($direction === 'deduct') {
                $available = $warehouseBalance->quantity ?? 0;
                if ($available < $qty) {
                    throw new \Exception("Insufficient warehouse stock for {$productName} (available {$available}, need {$qty})");
                }
            }

            $delta = $direction === 'deduct' ? $qty : -$qty; // positive = remove from stock

            if ($warehouseBalance) {
                $warehouseBalance->quantity -= $delta;
                $warehouseBalance->save();
            } elseif ($direction === 'return') {
                WarehouseInventoryBalance::create([
                    'warehouse_id' => $detail->warehouse_id,
                    'product_id' => $detail->product_id,
                    'batch_id' => $detail->product_batch_id,
                    'quantity' => $qty,
                ]);
            }

            if ($productBatch) {
                $productBatch->quantity -= $delta;
                $productBatch->save();
            }

            $inventoryTransaction = new InventoryTransaction();
            $inventoryTransaction->warehouse_id = $detail->warehouse_id;
        } else {
            $driver = $invoice->driver;
            if (!$driver || empty($driver->trip_id)) {
                throw new \Exception("Driver is not currently on an active trip - cannot adjust lorry stock for {$productName}");
            }

            $trip = Trip::where('uuid', $driver->trip_id)->first();
            $lorryId = $trip->lorry_id ?? null;
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

            if ($direction === 'deduct') {
                $available = $inventoryBalance ? $inventoryBalance->getBatchQuantity($detail->product_batch_id) : 0;
                if ($available < $qty) {
                    throw new \Exception("Insufficient lorry stock for {$productName} (available {$available}, need {$qty})");
                }
            }

            if ($inventoryBalance) {
                $inventoryBalance->updateBatchQuantity($detail->product_batch_id, $qty, $direction === 'deduct' ? 'subtract' : 'add');
            } elseif ($direction === 'return') {
                throw new \Exception("No inventory balance record found for driver's lorry - cannot return stock for {$productName}");
            }

            $inventoryTransaction = new InventoryTransaction();
            $inventoryTransaction->lorry_id = $lorryId;
        }

        $inventoryTransaction->type = $direction === 'deduct' ? InventoryTransaction::TYPE_STOCK_OUT : InventoryTransaction::TYPE_STOCK_IN;
        $inventoryTransaction->product_id = $detail->product_id;
        $inventoryTransaction->batch_id = $detail->product_batch_id;
        $inventoryTransaction->quantity = $direction === 'deduct' ? -$qty : $qty;
        $inventoryTransaction->date = now();
        $inventoryTransaction->user = Auth::user()->name;
        $inventoryTransaction->remark = 'Invoice #' . $invoice->invoiceno . ' - '
            . ($direction === 'return' ? 'Cancelled, stock returned' : 'Un-cancelled, stock re-deducted');
        $inventoryTransaction->save();
    }

    public function massupdateautocountinvoice(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $autocount_status = 'pending'; // all status will be updated to pending (success & failed)

        $count = invoice::whereIn('id',$ids)->update(['autocount_status'=>$autocount_status]);

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

        $warehouseItems = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id');

        $isDriverInvoice = !empty($invoice->trip_uuid);
        $driverLorryId = null;
        $driverProducts = [];

        if ($isDriverInvoice) {
            $driver = $invoice->driver;

            if (!$driver || empty($driver->trip_id)) {
                Flash::error('This invoice belongs to a driver who is not currently on a trip. Items cannot be added from the driver\'s stock right now.');

                return redirect(route('invoices.show', encrypt($id)));
            }

            $trip = Trip::where('uuid', $driver->trip_id)->first();
            $driverLorryId = $trip->lorry_id ?? null;

            $inventoryBalance = InventoryBalance::where('lorry_id', $driverLorryId)->first();

            if ($inventoryBalance) {
                $driverProducts = collect($inventoryBalance->batches_with_details)
                    ->unique('product_id')
                    ->pluck('product_name', 'product_id')
                    ->toArray();
            }
        }

        return view('invoices.detail', compact('id', 'warehouseItems', 'invoice', 'isDriverInvoice', 'driverLorryId', 'driverProducts'));
    }

    public function adddetail($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $invoice = $this->invoiceRepository->find($id);

        if (empty($invoice)) {
            Flash::error('Invoice not found');
            return redirect(route('invoices.index'));
        }

        $input = $request->all();

        // Check if this is a multiple items submission (from new modal)
        if ($request->has('items') && is_array($request->items) && count($request->items) > 0) {
            // Handle multiple items submission
            return $this->addMultipleItems($id, $request, $invoice);
        }
        // Handle single item submission (from other pages - detail page)
        else {
            $isDriverInvoice = !empty($invoice->trip_uuid);

            if ($isDriverInvoice) {
                // Driver invoice - item comes from the driver's own lorry stock, no warehouse involved
                if (!isset($input['product_batch_id']) || empty($input['product_batch_id'])) {
                    Flash::error('Please select a product batch');
                    return redirect()->back()->withInput();
                }

                $input['invoice_id'] = $id;
                Session::put('invoice_detail_data', $input);

                $res = $this->syncDriverDetailToXero($request);
                if ($res != null) {
                    return $res;
                }

                return redirect(route('invoices.show', encrypt($id)));
            }

            // Validate single item
            if (!isset($input['warehouse_id']) || empty($input['warehouse_id'])) {
                Flash::error('Please select a warehouse');
                return redirect()->back()->withInput();
            }

            $input['invoice_id'] = $id;
            Session::put('invoice_detail_data', $input);

            $res = $this->syncDetailToXero($request);
            if ($res != null) {
                return $res;
            }

            return redirect(route('invoices.show', encrypt($id)));
        }
    }

    /**
     * Handle adding multiple items to invoice at once
     */
    private function addMultipleItems($id, Request $request, $invoice)
    {
        $items = $request->input('items', []);
        
        if (empty($items)) {
            Flash::error('No items to add');
            return redirect()->back();
        }
        
        DB::beginTransaction();
        
        try {
            $totalAmount = 0;
            $addedItems = [];
            
            foreach ($items as $item) {
                // Validate each item
                if (empty($item['product_batch_id']) || empty($item['quantity']) || empty($item['price'])) {
                    throw new \Exception('Invalid item data. Please check all items.');
                }
                
                $batch = ProductBatch::find($item['product_batch_id']);
                if (!$batch) {
                    throw new \Exception('Product batch not found for item: ' . ($item['batch_code'] ?? 'Unknown'));
                }
                
                if ($batch->quantity < $item['quantity']) {
                    throw new \Exception('Insufficient stock for batch: ' . $batch->batch_code . '. Available: ' . $batch->quantity . ', Requested: ' . $item['quantity']);
                }
                
                // Get warehouse inventory (use default warehouse or first available)
                $warehouseInventory = WarehouseInventoryBalance::where('batch_id', $item['product_batch_id'])
                    ->where('quantity', '>=', $item['quantity'])
                    ->first();
                    
                if (!$warehouseInventory) {
                    throw new \Exception('No warehouse has sufficient stock for batch: ' . $batch->batch_code);
                }
                
                // Create invoice detail
                $invoiceDetail = new InvoiceDetail();
                $invoiceDetail->invoice_id = $id;
                $invoiceDetail->warehouse_id = $warehouseInventory->warehouse_id;
                $invoiceDetail->product_id = $item['product_id'];
                $invoiceDetail->product_batch_id = $item['product_batch_id'];
                $invoiceDetail->quantity = $item['quantity'];
                $invoiceDetail->price = $item['price'];
                $invoiceDetail->totalprice = $item['quantity'] * $item['price'];
                $invoiceDetail->remark = $item['remark'] ?? null;
                $invoiceDetail->save();
                
                // Deduct from warehouse inventory
                $warehouseInventory->decreaseQuantity($item['quantity']);
                
                // Deduct from product batch (global stock)
                $batch->decreaseQuantity($item['quantity'], 'Invoice #' . $invoice->invoiceno);
                
                // Create inventory transaction record
                $inventoryTransaction = new InventoryTransaction();
                $inventoryTransaction->type = 2; // Stock Out (Sale)
                $inventoryTransaction->warehouse_id = $warehouseInventory->warehouse_id;
                $inventoryTransaction->product_id = $item['product_id'];
                $inventoryTransaction->batch_id = $item['product_batch_id'];
                $inventoryTransaction->quantity = -$item['quantity'];
                $inventoryTransaction->date = now();
                $inventoryTransaction->user = Auth::user()->name;
                $inventoryTransaction->remark = 'Invoice- [' . $invoice->invoiceno . '] - Sale of product from warehouse -[' . $warehouseInventory->warehouse->name . ']';
                $inventoryTransaction->save();
                
                $totalAmount += $invoiceDetail->totalprice;
                $addedItems[] = $batch->batch_code;
            }
            
            // Update invoice payment if cash term
            if ($invoice->paymentterm == 1) {
                $existingPayment = InvoicePayment::where('invoice_id', $id)->first();
                $existingTotal = InvoiceDetail::where('invoice_id', $id)->sum('totalprice');
                
                if (!$existingPayment) {
                    $invoicePayment = new InvoicePayment();
                    $invoicePayment->invoice_id = $id;
                    $invoicePayment->type = 1;
                    $invoicePayment->customer_id = $invoice->customer_id;
                    $invoicePayment->driver_id = $invoice->driver_id;
                    $invoicePayment->status = 1; // Cash payment - always created as Completed
                    $invoicePayment->approve_by = Auth::user()->email;
                    $invoicePayment->approve_at = now();
                    $invoicePayment->amount = $existingTotal;
                    $invoicePayment->save();
                } else {
                    $existingPayment->amount = $existingTotal;
                    $existingPayment->save();
                }
            }
            
            DB::commit();
            
            Flash::success(count($items) . ' item(s) added successfully. Items: ' . implode(', ', $addedItems));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error adding items: ' . $e->getMessage());
            return redirect()->back();
        }
        
        return redirect(route('invoices.show', encrypt($id)));
    }
    
    public function getAvailableBatches($invoice_id)
    {
        $invoice = Invoice::findOrFail($invoice_id);
        
        // Get product batches that have stock and are active
        $batches = ProductBatch::where('status', ProductBatch::STATUS_ACTIVE)
            ->where('quantity', '>', 0)
            ->with('product')
            ->get()
            ->map(function($batch) use ($invoice) {
                // Get special price if exists
                $specialPrice = SpecialPrice::where('customer_id', $invoice->customer_id)
                    ->where('product_id', $batch->product_id)
                    ->first();
                
                $price = $specialPrice ? $specialPrice->price : ($batch->product->price ?? 0);
                
                return [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'product_id' => $batch->product_id,
                    'product_name' => $batch->product->name ?? 'Unknown',
                    'quantity' => $batch->quantity,
                    'price' => $price,
                    'expiry_date' => $batch->expiry_date
                ];
            });
        
        return response()->json(['batches' => $batches]);
    }

    private function syncDetailToXero(Request $request) 
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

        // Validate required fields including warehouse_id
        if (!isset($input['product_batch_id']) || empty($input['product_batch_id'])) {
            Flash::error('Please select a product batch');
            return redirect()->back()->withInput();
        }

        if (!isset($input['warehouse_id']) || empty($input['warehouse_id'])) {
            Flash::error('Please select a warehouse');
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Check if we're updating an existing detail
            $existingDetail = null;
            if (isset($input['detail_id']) && !empty($input['detail_id'])) {
                $existingDetail = InvoiceDetail::find($input['detail_id']);
            }
            // Get the product batch for the new item
            $newProductBatch = ProductBatch::find($input['product_batch_id']);
            
            if (empty($newProductBatch)) {
                throw new \Exception('Product batch not found');
            }

            // Check warehouse inventory instead of main batch quantity
            $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $input['warehouse_id'])
                ->where('batch_id', $input['product_batch_id'])
                ->first();

            if (!$warehouseInventory) {
                throw new \Exception('This batch is not available in the selected warehouse');
            }

            if ($warehouseInventory->quantity < $input['quantity']) {
                throw new \Exception('Insufficient quantity in warehouse. Available: ' . $warehouseInventory->quantity . ', Requested: ' . $input['quantity']);
            }

            // Handle existing detail reversal if we're updating
            if ($existingDetail) {
                // Check if the existing detail has warehouse_id (for backward compatibility)
                if ($existingDetail->warehouse_id && $existingDetail->product_batch_id) {
                    // Get the original warehouse inventory that was used
                    $oldWarehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $existingDetail->warehouse_id)
                        ->where('batch_id', $existingDetail->product_batch_id)
                        ->first();
                    
                    if ($oldWarehouseInventory) {
                        // Add back the original quantity to the old warehouse inventory
                        $oldWarehouseInventory->increaseQuantity($existingDetail->quantity);

                        // Create inventory transaction record for the reversal
                        $reversalTransaction = new InventoryTransaction();
                        $reversalTransaction->type = 1; // Stock In (Reversal)
                        $reversalTransaction->warehouse_id = $existingDetail->warehouse_id;
                        $reversalTransaction->product_id = $existingDetail->product_id;
                        $reversalTransaction->batch_id = $existingDetail->product_batch_id;
                        $reversalTransaction->quantity = $existingDetail->quantity;
                        $reversalTransaction->date = now();
                        $reversalTransaction->user = Auth::user()->name;
                        $reversalTransaction->remark = 'Reversal for invoice #' . $invoice->invoiceno . ' - Detail ID: ' . $existingDetail->id;
                        $reversalTransaction->save();

                        // Note: No need to update lorry inventory balance anymore since we're using warehouse
                        // The old code for lorry inventory balance has been removed
                    }
                }
            }

            // Deduct quantity from warehouse inventory
            $warehouseInventory->decreaseQuantity($input['quantity']);
            $newProductBatch->decreaseQuantity($input['quantity']);

            // Create or update invoice detail
            if ($existingDetail) {
                // Update existing detail
                $existingDetail->warehouse_id = $input['warehouse_id']; // Add warehouse_id
                $existingDetail->product_id = $input['product_id'];
                $existingDetail->product_batch_id = $input['product_batch_id'];
                $existingDetail->quantity = $input['quantity'];
                $existingDetail->price = $input['price'];
                $existingDetail->totalprice = $input['quantity'] * $input['price'];
                $existingDetail->remark = $input['remark'] ?? null;
                $existingDetail->save();
                
                $invoicedetail = $existingDetail;
            } else {
                // Create new detail
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $input['invoice_id'];
                $invoicedetail->warehouse_id = $input['warehouse_id']; // Add warehouse_id
                $invoicedetail->product_id = $input['product_id'];
                $invoicedetail->product_batch_id = $input['product_batch_id'];
                $invoicedetail->quantity = $input['quantity'];
                $invoicedetail->price = $input['price'];
                $invoicedetail->totalprice = $input['quantity'] * $input['price'];
                $invoicedetail->remark = $input['remark'] ?? null;
                $invoicedetail->save();
            }

            // Create inventory transaction record for the new sale (using warehouse)
            $inventoryTransaction = new InventoryTransaction();
            $inventoryTransaction->type = 2; // Stock Out (Sale)
            $inventoryTransaction->warehouse_id = $input['warehouse_id'];
            $inventoryTransaction->product_id = $input['product_id'];
            $inventoryTransaction->batch_id = $input['product_batch_id']; 
            $inventoryTransaction->quantity = -$input['quantity']; 
            $inventoryTransaction->date = now();
            $inventoryTransaction->user = Auth::user()->name;
            $inventoryTransaction->remark = 'Invoice- [' . ($invoice->invoiceno ?? '') . '] - Sale of product from warehouse -['.$invoicedetail->warehouse->name.']';
            $inventoryTransaction->save();


            // Handle invoice payment if cash term (keep this as is)
            if ($invoice->paymentterm == 1) {
                $id = $input['invoice_id'];
                $invoicePayment = InvoicePayment::where('invoice_id', $id)->first();
                
                $totalAmount = InvoiceDetail::where('invoice_id', $id)->sum('totalprice');

                if (!$invoicePayment) {
                    $invoicepayment_new = new InvoicePayment();
                    $invoicepayment_new->invoice_id = $id;
                    $invoicepayment_new->type = 1;
                    $invoicepayment_new->customer_id = $invoice->customer_id;
                    $invoicepayment_new->amount = $totalAmount;
                    $invoicepayment_new->status = 1; // Cash payment - always created as Completed
                    $invoicepayment_new->driver_id = $invoice->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->email;
                    $invoicepayment_new->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment_new->save();
                } else {
                    $invoicePayment->status = 1;
                    $invoicePayment->amount = $totalAmount;
                    $invoicePayment->save();
                }
            }
            
            DB::commit();
            
            $action = $existingDetail ? 'updated' : 'saved';
            Flash::success('Invoice Detail ' . $action . ' successfully. Stock deducted: ' . $input['quantity'] . ' units from batch [' . $newProductBatch->batch_code . '] in warehouse [' . $warehouseInventory->warehouse->name . ']');
            
            Session::forget('invoice_detail_data');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error saving invoice detail: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Add/update a single invoice detail on a DRIVER-created invoice, deducting from
     * that driver's current lorry stock (InventoryBalance) instead of a warehouse.
     */
    private function syncDriverDetailToXero(Request $request)
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

        if (empty($invoice->trip_uuid)) {
            Flash::error('This invoice is not a driver invoice.');
            return redirect()->back();
        }

        $driver = $invoice->driver;

        if (!$driver || empty($driver->trip_id)) {
            Flash::error('Driver is not currently on an active trip. Cannot add item from driver stock.');
            return redirect()->back()->withInput();
        }

        $trip = Trip::where('uuid', $driver->trip_id)->first();
        $lorryId = $trip->lorry_id ?? null;

        if (!isset($input['product_batch_id']) || empty($input['product_batch_id'])) {
            Flash::error('Please select a product batch');
            return redirect()->back()->withInput();
        }

        $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

        if (!$inventoryBalance) {
            Flash::error('No stock found for the driver\'s current lorry');
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            // Check if we're updating an existing detail
            $existingDetail = null;
            if (isset($input['detail_id']) && !empty($input['detail_id'])) {
                $existingDetail = InvoiceDetail::find($input['detail_id']);
            }

            // Reverse the old quantity back into the lorry balance first if we're editing
            if ($existingDetail && $existingDetail->product_batch_id) {
                $inventoryBalance->updateBatchQuantity($existingDetail->product_batch_id, $existingDetail->quantity, 'add');

                $reversalTransaction = new InventoryTransaction();
                $reversalTransaction->type = 1; // Stock In (Reversal)
                $reversalTransaction->lorry_id = $lorryId;
                $reversalTransaction->product_id = $existingDetail->product_id;
                $reversalTransaction->batch_id = $existingDetail->product_batch_id;
                $reversalTransaction->quantity = $existingDetail->quantity;
                $reversalTransaction->date = now();
                $reversalTransaction->user = Auth::user()->name;
                $reversalTransaction->remark = 'Reversal for invoice #' . $invoice->invoiceno . ' - Detail ID: ' . $existingDetail->id;
                $reversalTransaction->save();
            }

            $availableQty = $inventoryBalance->getBatchQuantity($input['product_batch_id']);
            if ($availableQty < $input['quantity']) {
                throw new \Exception('Insufficient stock in driver\'s lorry. Available: ' . $availableQty . ', Requested: ' . $input['quantity']);
            }

            // Deduct quantity from the driver's lorry inventory balance
            $inventoryBalance->updateBatchQuantity($input['product_batch_id'], $input['quantity'], 'subtract');

            // Create or update invoice detail (warehouse_id stays null - marks this as driver-sourced)
            if ($existingDetail) {
                $existingDetail->warehouse_id = null;
                $existingDetail->product_id = $input['product_id'];
                $existingDetail->product_batch_id = $input['product_batch_id'];
                $existingDetail->quantity = $input['quantity'];
                $existingDetail->price = $input['price'];
                $existingDetail->totalprice = $input['quantity'] * $input['price'];
                $existingDetail->remark = $input['remark'] ?? null;
                // This function always deducts from the lorry above (line 986) -
                // without this flag, editInvoice()'s later restoration step
                // (API side) has no way to know this item's stock was ever
                // taken from the lorry, and would silently skip returning it.
                $existingDetail->deducted_from_inventory = true;
                $existingDetail->save();

                $invoicedetail = $existingDetail;
            } else {
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $input['invoice_id'];
                $invoicedetail->warehouse_id = null;
                $invoicedetail->product_id = $input['product_id'];
                $invoicedetail->product_batch_id = $input['product_batch_id'];
                $invoicedetail->quantity = $input['quantity'];
                $invoicedetail->price = $input['price'];
                $invoicedetail->totalprice = $input['quantity'] * $input['price'];
                $invoicedetail->remark = $input['remark'] ?? null;
                $invoicedetail->deducted_from_inventory = true;
                $invoicedetail->save();
            }

            // Create inventory transaction record for the new sale (using driver's lorry)
            $inventoryTransaction = new InventoryTransaction();
            $inventoryTransaction->type = 2; // Stock Out (Sale)
            $inventoryTransaction->lorry_id = $lorryId;
            $inventoryTransaction->product_id = $input['product_id'];
            $inventoryTransaction->batch_id = $input['product_batch_id'];
            $inventoryTransaction->quantity = -$input['quantity'];
            $inventoryTransaction->date = now();
            $inventoryTransaction->user = Auth::user()->name;
            $inventoryTransaction->remark = 'Invoice- [' . $invoice->invoiceno . '] - Admin added item to driver\'s lorry stock';
            $inventoryTransaction->save();

            // Handle invoice payment if cash term (same as admin/warehouse flow)
            if ($invoice->paymentterm == 1) {
                $invoiceId = $input['invoice_id'];
                $invoicePayment = InvoicePayment::where('invoice_id', $invoiceId)->first();

                $totalAmount = InvoiceDetail::where('invoice_id', $invoiceId)->sum('totalprice');

                if (!$invoicePayment) {
                    $invoicepayment_new = new InvoicePayment();
                    $invoicepayment_new->invoice_id = $invoiceId;
                    $invoicepayment_new->type = 1;
                    $invoicepayment_new->customer_id = $invoice->customer_id;
                    $invoicepayment_new->amount = $totalAmount;
                    $invoicepayment_new->status = 1; // Cash payment - always created as Completed
                    $invoicepayment_new->driver_id = $invoice->driver_id;
                    $invoicepayment_new->approve_by = Auth::user()->email;
                    $invoicepayment_new->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment_new->save();
                } else {
                    $invoicePayment->status = 1;
                    $invoicePayment->amount = $totalAmount;
                    $invoicePayment->save();
                }
            }

            DB::commit();

            $action = $existingDetail ? 'updated' : 'saved';
            Flash::success('Invoice Detail ' . $action . ' successfully. Stock deducted: ' . $input['quantity'] . ' units from driver\'s lorry stock.');

            Session::forget('invoice_detail_data');

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error saving invoice detail: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

   public function deletedetail($id, Request $request){
        $id = Crypt::decrypt($id);

        $invoicedetail = InvoiceDetail::with('invoice.driver')->where('id', $id)->first();

        if (empty($invoicedetail)) {
            Flash::error('Invoice Detail not found');
            return redirect()->back();
        }

        $invoice = $invoicedetail->invoice;

        // Driver-sourced item (warehouse_id is null) - if the driver is not currently on
        // a trip there is no active lorry balance to return stock to, so the admin must
        // pick a warehouse before we proceed. Check this before starting any writes.
        $returnWarehouseId = null;
        if (empty($invoicedetail->warehouse_id)) {
            $driver = $invoice->driver;
            $driverOnTrip = $driver && !empty($driver->trip_id);

            if (!$driverOnTrip) {
                $returnWarehouseId = $request->input('return_warehouse_id');
                if (empty($returnWarehouseId)) {
                    Flash::error('Driver is not currently on a trip. Please select a warehouse to return this stock to.');
                    return redirect()->back();
                }
            }
        }

        DB::beginTransaction();

        try {
            // Get the product batch
            $productBatch = ProductBatch::find($invoicedetail->product_batch_id);

            if ($productBatch) {
                $reversalTransaction = new InventoryTransaction();
                $reversalTransaction->type = 1;
                $reversalTransaction->product_id = $invoicedetail->product_id;
                $reversalTransaction->batch_id = $invoicedetail->product_batch_id;
                $reversalTransaction->quantity = $invoicedetail->quantity;
                $reversalTransaction->date = now();
                $reversalTransaction->user = Auth::user()->name;
                $reversalTransaction->remark = 'Reversal from deleted invoice detail - Invoice #' . ($invoice->invoiceno ?? '');

                if ($invoicedetail->warehouse_id) {
                    // Admin invoice — add back to product batch quantity
                    $productBatch->quantity += $invoicedetail->quantity;
                    $productBatch->save();

                    // Return stock to warehouse inventory balance
                    $warehouseBalance = \App\Models\WarehouseInventoryBalance::where('warehouse_id', $invoicedetail->warehouse_id)
                        ->where('batch_id', $invoicedetail->product_batch_id)
                        ->first();

                    if ($warehouseBalance) {
                        $warehouseBalance->quantity += $invoicedetail->quantity;
                        $warehouseBalance->save();
                    } else {
                        \App\Models\WarehouseInventoryBalance::create([
                            'warehouse_id' => $invoicedetail->warehouse_id,
                            'product_id'   => $invoicedetail->product_id,
                            'batch_id'     => $invoicedetail->product_batch_id,
                            'quantity'     => $invoicedetail->quantity,
                        ]);
                    }

                    $reversalTransaction->warehouse_id = $invoicedetail->warehouse_id;
                } elseif ($returnWarehouseId) {
                    // Driver not currently on a trip — admin chose a warehouse to return stock to instead
                    $warehouseBalance = WarehouseInventoryBalance::where('warehouse_id', $returnWarehouseId)
                        ->where('batch_id', $invoicedetail->product_batch_id)
                        ->first();

                    if ($warehouseBalance) {
                        $warehouseBalance->quantity += $invoicedetail->quantity;
                        $warehouseBalance->save();
                    } else {
                        WarehouseInventoryBalance::create([
                            'warehouse_id' => $returnWarehouseId,
                            'product_id'   => $invoicedetail->product_id,
                            'batch_id'     => $invoicedetail->product_batch_id,
                            'quantity'     => $invoicedetail->quantity,
                        ]);
                    }

                    $productBatch->quantity += $invoicedetail->quantity;
                    $productBatch->save();

                    $reversalTransaction->warehouse_id = $returnWarehouseId;
                } else {
                    // Driver invoice, driver currently on a trip — return stock to their current lorry's inventory balance
                    $driver = $invoice->driver;
                    $trip = Trip::where('uuid', $driver->trip_id ?? null)->first();
                    $lorryId = $trip->lorry_id ?? null;

                    $inventorybalance = InventoryBalance::where('lorry_id', $lorryId)->first();

                    if ($inventorybalance) {
                        $inventorybalance->updateBatchQuantity(
                            $invoicedetail->product_batch_id,
                            $invoicedetail->quantity,
                            'add'
                        );
                    }

                    $reversalTransaction->lorry_id = $lorryId;
                }

                $reversalTransaction->save();
            }

            // Update invoice payment if cash term
            if ($invoice->paymentterm == 1) {
                $totalAmount = InvoiceDetail::where('invoice_id', $invoice->id)->sum('totalprice');
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
                
                if ($invoicePayment) {
                    if ($totalAmount > 0) {
                        $invoicePayment->amount = $totalAmount;
                        $invoicePayment->save();
                    } else {
                        // If no details left, delete or cancel the payment
                        $invoicePayment->status = 2; // Cancelled
                        $invoicePayment->save();
                    }
                }
            }

            // Delete the invoice detail
            $invoicedetail->delete();

            DB::commit();

            Flash::success('Invoice Detail deleted successfully. Stock returned to batch.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error deleting invoice detail: ' . $e->getMessage());
            return redirect()->back();
        }

        return redirect(route('invoices.show', encrypt($invoicedetail->invoice_id)));
    }

    /**
     * Update the price and/or quantity of a single invoice detail line
     * item. A quantity change adjusts the source inventory by the delta
     * (increase = deduct more, decrease = return the difference) and logs
     * an InventoryTransaction for it, mirroring the driver-lorry vs
     * warehouse handling already used by adddetail()/deletedetail().
     */
    public function updatedetail($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $invoicedetail = InvoiceDetail::with('invoice.driver')->where('id', $id)->first();

        if (empty($invoicedetail)) {
            Flash::error('Invoice Detail not found');
            return redirect()->back();
        }

        $invoice = $invoicedetail->invoice;

        if (strtolower($invoice->autocount_status ?? '') === 'completed') {
            Flash::error('This invoice has been submitted to Autocount and can no longer be edited.');
            return redirect()->back();
        }

        $request->validate([
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:1',
        ]);

        $oldQuantity = $invoicedetail->quantity;
        $newQuantity = (float) $request->input('quantity');
        // Positive = quantity went up (deduct the difference from stock),
        // negative = quantity went down (return the difference to stock).
        $delta = $newQuantity - $oldQuantity;

        DB::beginTransaction();

        try {
            if ($delta != 0) {
                $productBatch = ProductBatch::find($invoicedetail->product_batch_id);

                if ($invoicedetail->warehouse_id) {
                    // Warehouse-sourced item
                    $warehouseBalance = \App\Models\WarehouseInventoryBalance::where('warehouse_id', $invoicedetail->warehouse_id)
                        ->where('batch_id', $invoicedetail->product_batch_id)
                        ->first();

                    if ($delta > 0) {
                        $availableQty = $warehouseBalance->quantity ?? 0;
                        if ($availableQty < $delta) {
                            throw new \Exception('Insufficient warehouse stock. Available: ' . $availableQty . ', additional needed: ' . $delta);
                        }
                    }

                    if ($warehouseBalance) {
                        $warehouseBalance->quantity -= $delta;
                        $warehouseBalance->save();
                    } elseif ($delta < 0) {
                        // No existing balance row but we're returning stock - create one
                        \App\Models\WarehouseInventoryBalance::create([
                            'warehouse_id' => $invoicedetail->warehouse_id,
                            'product_id'   => $invoicedetail->product_id,
                            'batch_id'     => $invoicedetail->product_batch_id,
                            'quantity'     => -$delta,
                        ]);
                    }

                    if ($productBatch) {
                        $productBatch->quantity -= $delta;
                        $productBatch->save();
                    }

                    $inventoryTransaction = new InventoryTransaction();
                    $inventoryTransaction->warehouse_id = $invoicedetail->warehouse_id;
                } else {
                    // Driver (lorry) sourced item - needs an active trip to know which lorry to adjust
                    $driver = $invoice->driver;

                    if (!$driver || empty($driver->trip_id)) {
                        throw new \Exception('Driver is not currently on an active trip. Cannot adjust quantity for this driver-sourced item.');
                    }

                    $trip = Trip::where('uuid', $driver->trip_id)->first();
                    $lorryId = $trip->lorry_id ?? null;
                    $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

                    if ($delta > 0) {
                        $availableQty = $inventoryBalance ? $inventoryBalance->getBatchQuantity($invoicedetail->product_batch_id) : 0;
                        if ($availableQty < $delta) {
                            throw new \Exception('Insufficient stock in driver\'s lorry. Available: ' . $availableQty . ', additional needed: ' . $delta);
                        }
                    }

                    if ($inventoryBalance) {
                        $inventoryBalance->updateBatchQuantity(
                            $invoicedetail->product_batch_id,
                            abs($delta),
                            $delta > 0 ? 'subtract' : 'add'
                        );
                    }

                    $inventoryTransaction = new InventoryTransaction();
                    $inventoryTransaction->lorry_id = $lorryId;
                }

                $inventoryTransaction->type = $delta > 0 ? InventoryTransaction::TYPE_STOCK_OUT : InventoryTransaction::TYPE_STOCK_IN;
                $inventoryTransaction->product_id = $invoicedetail->product_id;
                $inventoryTransaction->batch_id = $invoicedetail->product_batch_id;
                $inventoryTransaction->quantity = -$delta;
                $inventoryTransaction->date = now();
                $inventoryTransaction->user = Auth::user()->name;
                $inventoryTransaction->remark = 'Invoice # [' . $invoice->invoiceno . '] - Quantity '
                    . ($delta > 0 ? 'increased' : 'decreased') . ' on edit ('
                    . abs($delta) . ' units ' . ($delta > 0 ? 'deducted' : 'returned') . ')';
                $inventoryTransaction->save();
            }

            $invoicedetail->quantity = $newQuantity;
            $invoicedetail->price = $request->input('price');
            $invoicedetail->totalprice = $newQuantity * $invoicedetail->price;
            $invoicedetail->save();

            // Keep the cash-payment amount in sync with the new line total
            if ($invoice->paymentterm == 1) {
                $totalAmount = InvoiceDetail::where('invoice_id', $invoice->id)->sum('totalprice');
                $invoicePayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
                if ($invoicePayment) {
                    $invoicePayment->amount = $totalAmount;
                    $invoicePayment->save();
                }
            }

            DB::commit();

            Flash::success('Invoice Detail updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating invoice detail: ' . $e->getMessage());
            return redirect()->back();
        }

        return redirect(route('invoices.show', encrypt($invoicedetail->invoice_id)));
    }

    public function print()
    {
        return view('invoices.print');
    }

    /**
     * Optimized version - calculates credit in a single query
     */
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
            return [
                'totalprice' => 0,
                'paid' => 0,
                'credit' => 0
            ];
        }
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

        $itemCount = count($invoice['invoicedetail']);
        // Each item renders as 2 rows (price/qty + product name). Use a generous
        // per-item estimate (70pt) to safely cover text wrapping on narrow receipt width.
        // Base (700pt) covers company header, address blocks, totals, and footer.
        $height = ($itemCount * 70) + 700;

        $creditData = $this->calculateCustomerCredit(
            $invoice->customer_id, 
            $invoice->updated_at
        );
        
        $invoice->newcredit = round($creditData['credit'] ?? 0, 2);

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
            dd($e->getMessage());
        }

    }

    public static function convert($number)
    {
        $number = round($number, 2);
        $dollars = floor($number);
        $cents = round(($number - $dollars) * 100);
        
        $words = self::convertNumberToWords($dollars);
        
        if ($cents > 0) {
            $words .= ' AND ' . self::convertNumberToWords($cents) . ' CENTS';
        }
        
        return $words;
    }
    
    private static function convertNumberToWords($number)
    {
        $words = '';
        
        $units = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 
                  'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 
                  'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
        
        if ($number == 0) {
            return 'ZERO';
        }
        
        if ($number < 20) {
            $words = $units[$number];
        } elseif ($number < 100) {
            $words = $tens[floor($number / 10)];
            if ($number % 10 > 0) {
                $words .= ' ' . $units[$number % 10];
            }
        } elseif ($number < 1000) {
            $words = $units[floor($number / 100)] . ' HUNDRED';
            if ($number % 100 > 0) {
                $words .= ' ' . self::convertNumberToWords($number % 100);
            }
        } elseif ($number < 1000000) {
            $words = self::convertNumberToWords(floor($number / 1000)) . ' THOUSAND';
            if ($number % 1000 > 0) {
                $words .= ' ' . self::convertNumberToWords($number % 1000);
            }
        } else {
            $words = self::convertNumberToWords(floor($number / 1000000)) . ' MILLION';
            if ($number % 1000000 > 0) {
                $words .= ' ' . self::convertNumberToWords($number % 1000000);
            }
        }
        
        return $words;
    }

    function formatMalaysianAddressSimple($address, $maxLines = 4) 
    {
        if (empty($address)) {
            return '';
        }
        
        // First, normalize to single line with commas as separators
        // Replace newlines with commas
        $address = preg_replace('/\r\n|\r|\n/', ', ', $address);
        
        // Split by commas
        $parts = explode(',', $address);
        
        // Trim each part and remove empty ones
        $parts = array_map('trim', $parts);
        $parts = array_filter($parts, function($part) {
            return $part !== '';
        });
        $parts = array_values($parts);
        
        if (count($parts) <= $maxLines) {
            return implode("\n", $parts);
        }
        
        // For address format: street, area, postcode+city, state
        // We want: line1: street, line2: area, line3: postcode+city, line4: state
        
        $streetParts = [];
        $areaParts = [];
        $postcodeCity = '';
        $statePart = '';
        
        foreach ($parts as $part) {
            // Check if this part contains a postcode (5 digits)
            if (preg_match('/\b\d{5}\b/', $part)) {
                $postcodeCity = $part;
            }
            // Check if this is a state (contains Wilayah, Selangor, etc.)
            elseif (preg_match('/(Wilayah|Selangor|Johor|Kedah|Kelantan|Melaka|Negeri Sembilan|Pahang|Perak|Perlis|Pulau Pinang|Sabah|Sarawak|Terengganu)/i', $part)) {
                $statePart = $part;
            }
            // Check if this is an area (contains Bandar, Taman, etc.)
            elseif (preg_match('/(Bandar|Taman|Seksyen|Section|Pusat|Kompleks)/i', $part)) {
                $areaParts[] = $part;
            }
            // Otherwise treat as street address
            else {
                $streetParts[] = $part;
            }
        }
        
        $result = [];
        
        // Line 1: Street (combine multiple street parts)
        if (!empty($streetParts)) {
            $result[] = implode(', ', $streetParts);
        }
        
        // Line 2: Area (combine multiple area parts)
        if (!empty($areaParts)) {
            $result[] = implode(', ', $areaParts);
        }
        
        // Line 3: Postcode + City
        if (!empty($postcodeCity)) {
            $result[] = $postcodeCity;
        }
        
        // Line 4: State
        if (!empty($statePart)) {
            $result[] = $statePart;
        }
        
        // If we have more than 4 lines, combine as needed
        while (count($result) > $maxLines) {
            $last = array_pop($result);
            $prev = array_pop($result);
            $result[] = $prev . ', ' . $last;
        }
        
        return implode("\n", $result);
    }

    public function getInvoiceSimplePDF($id, $function)
    {
        $id = Crypt::decrypt($id);
        $mode = request()->query('mode', 'invoice');

        $invoice = Invoice::where('id', $id)
            ->with('customer')
            ->with('driver')
            ->with('invoicedetail.product')
            ->with('invoicedetail.batch')
            ->first();

        if (empty($invoice)) {
            abort(404);
        }

        // Calculate totals
        $totalAmount = 0;
        $totalQuantity = 0;
        foreach ($invoice->invoicedetail as $detail) {
            $totalAmount += $detail->totalprice;
            $totalQuantity += $detail->quantity;
        }

        $customer_billing_address = $this->formatMalaysianAddressSimple($invoice->customer->billing_address ?? '', 4);
        
        // Get company info
        $companyInfo = [
            'name' => 'GW NOODLES SDN BHD',
            'ssm' => 'TIN: C24694011050',
            'address1' => '23 JLN SETIA PERNIAGAAN 9,81100 JOHOR BAHRU, MALAYSIA',
            'phone' => '016-723-7931',
            'msic_code' => '10799'
        ];

        // Convert amount to words
        $totalAmountText = $this->convertNumberToWords($totalAmount);

        try {
            $pdf = Pdf::loadView('invoices.pdf_print', array(
                'invoice' => $invoice,
                'companyInfo' => $companyInfo,
                'totalAmount' => $totalAmount,
                'totalQuantity' => $totalQuantity,
                'customer_billing_address' => $customer_billing_address,
                'totalAmountText' => $totalAmountText,
                'mode' => $mode
            ));

            // Set PDF options with Tahoma font support
            $pdf->setOptions([
                'isPhpEnabled' => true, 
                'isRemoteEnabled' => true,
                'defaultFont' => 'helvetica', // Fallback font
                'fontCache' => storage_path('fonts/'), // Cache fonts
            ]);

            // Register Tahoma font if available
            $fontPath = public_path('fonts/Tahoma.ttf');
            if (file_exists($fontPath)) {
                $pdf->getDomPDF()->set_option('fontDir', public_path('fonts'));
                $pdf->getDomPDF()->set_option('fontCache', storage_path('fonts/'));
            }

            if ($function == 'download') {
                return $pdf->setPaper('a4', 'portrait')
                    ->download('invoice_' . $invoice->invoiceno . '.pdf');
            } elseif ($function == 'view') {
                return $pdf->setPaper('a4', 'portrait')
                    ->stream('invoice_' . $invoice->invoiceno . '.pdf');
            }
        } catch (Exception $e) {
            dd($e->getMessage());
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