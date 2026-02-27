<?php

namespace App\Http\Controllers;

use App\DataTables\DeliveryOrderDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDeliveryOrderRequest;
use App\Http\Requests\UpdateDeliveryOrderRequest;
use App\Repositories\DeliveryOrderRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lorry;
use App\Models\CommissionByVendors;
use App\Models\DeliveryOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\Claim;

class DeliveryOrderController extends AppBaseController
{
    /** @var DeliveryOrderRepository $deliveryOrderRepository*/
    private $deliveryOrderRepository;

    public function __construct(DeliveryOrderRepository $deliveryOrderRepo)
    {
        $this->deliveryOrderRepository = $deliveryOrderRepo;
    }

    /**
     * Display a listing of the DeliveryOrder.
     *
     * @param DeliveryOrderDataTable $deliveryOrderDataTable
     *
     * @return Response
     */
    public function index(DeliveryOrderDataTable $deliveryOrderDataTable)
    {
        return $deliveryOrderDataTable->render('delivery_orders.index');
    }

    /**
     * Show the form for creating a new DeliveryOrder.
     *
     * @return Response
     */
    public function create()
    {
        return view('delivery_orders.create');
    }

    /**
     * Store a newly created DeliveryOrder in storage.
     *
     * @param CreateDeliveryOrderRequest $request
     *
     * @return Response
     */
    public function store(CreateDeliveryOrderRequest $request)
    {
        $input = $request->all();
        if($input['shipweight'] == ''){
            $input['shipweight'] = $input['weight'];
        }
        $input['billingrate'] = app('App\Http\Controllers\ItemController')->getBillingRate($request);
        $input['commissionrate'] = app('App\Http\Controllers\ItemController')->getCommissionRate($request);
        $input['calstatus'] = 0;
        $input['date'] = date_create($input['date']);
        $input['fees'] = $input['fees'] == null ? 0 : $input['fees'];
        $input['tol'] = $input['tol'] == null ? 0 : $input['tol'];
        $deliveryOrder = $this->deliveryOrderRepository->create($input);

        $claim_amount = $deliveryOrder->tol + $deliveryOrder->fees;
        $claim_no = 'DO_'.date_format(date_create($deliveryOrder->date),"Ymd").'_'.$deliveryOrder->dono;
        $claim_driverid = $deliveryOrder->driver_id;
        $claim_date = date_create($deliveryOrder->date);
        $claim_deliveryorderid = $deliveryOrder->id;
        $claim_description = 'Tol+Loading/Unloading Fees';
        if($input['status']==1){
            if($claim_amount > 0){
                $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0);
    
                if($claim->count() == 0){
                    $claimnew = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                    ->where('editable',0)
                    ->create(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                }else{
                    $claimupdate = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                    ->where('editable',0)
                    ->update(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                }
            }else{
                $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
            }
        }else{
            $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
        }

        Flash::success('Delivery Order saved successfully.');

        return redirect(route('deliveryOrders.index'));
    }

    /**
     * Display the specified DeliveryOrder.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        return view('delivery_orders.show')->with('deliveryOrder', $deliveryOrder);
    }

    /**
     * Show the form for editing the specified DeliveryOrder.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        return view('delivery_orders.edit')->with('deliveryOrder', $deliveryOrder);
    }

    /**
     * Update the specified DeliveryOrder in storage.
     *
     * @param int $id
     * @param UpdateDeliveryOrderRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDeliveryOrderRequest $request)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }
        $input = $request->all();
        if($input['shipweight'] == ''){
            $input['shipweight'] = $input['weight'];
        }
        $input['billingrate'] = app('App\Http\Controllers\ItemController')->getBillingRate($request);
        $input['commissionrate'] = app('App\Http\Controllers\ItemController')->getCommissionRate($request);
        $input['calstatus'] = 0;
        $input['date'] = date_create($input['date']);
        $input['fees'] = $input['fees'] == null ? 0 : $input['fees'];
        $input['tol'] = $input['tol'] == null ? 0 : $input['tol'];
        $deliveryOrder = $this->deliveryOrderRepository->update($input, $id);

        $claim_amount = $deliveryOrder->tol + $deliveryOrder->fees;
        $claim_no = 'DO_'.date_format(date_create($deliveryOrder->date),"Ymd").'_'.$deliveryOrder->dono;
        $claim_driverid = $deliveryOrder->driver_id;
        $claim_date = date_create($deliveryOrder->date);
        $claim_deliveryorderid = $deliveryOrder->id;
        $claim_description = 'Tol+Loading/Unloading Fees';
        if($input['status']==1){
            if($claim_amount > 0){
                $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0);
    
                if($claim->count() == 0){
                    $claimnew = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                    ->where('editable',0)
                    ->create(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                }else{
                    $claimupdate = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                    ->where('editable',0)
                    ->update(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                }
            }else{
                $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
            }
        }else{
            $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
        }

        Flash::success('Delivery Order updated successfully.');

        return redirect(route('deliveryOrders.index'));
    }

    /**
     * Remove the specified DeliveryOrder from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(route('deliveryOrders.index'));
        }

        $this->deliveryOrderRepository->delete($id);

        $claim = Claim::where('deliveryorder_id',$id)->where('editable',0)->delete();

        Flash::success('Delivery Order deleted successfully.');

        return redirect(route('deliveryOrders.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewDeliveryOrder('.Auth::id().',\'DeliveryOrders\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = DeliveryOrder::destroy($ids);
        $claim = Claim::whereIn('deliveryorder_id',$ids)->where('editable',0)->delete();

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = DeliveryOrder::whereIn('id',$ids)->update(['status'=>$status]);

        foreach($ids as $id){
            $deliveryOrder = $this->deliveryOrderRepository->find($id);
    
            $claim_amount = $deliveryOrder->tol + $deliveryOrder->fees;
            $claim_no = 'DO_'.date_format(date_create($deliveryOrder->date),"Ymd").'_'.$deliveryOrder->dono;
            $claim_driverid = $deliveryOrder->driver_id;
            $claim_date = date_create($deliveryOrder->date);
            $claim_deliveryorderid = $deliveryOrder->id;
            $claim_description = 'Tol+Loading/Unloading Fees';

            if($status==1){
                if($claim_amount > 0){
                    $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0);
        
                    if($claim->count() == 0){
                        $claimnew = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                        ->where('editable',0)
                        ->create(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                    }else{
                        $claimupdate = Claim::where('deliveryorder_id',$claim_deliveryorderid)
                        ->where('editable',0)
                        ->update(['date'=>$claim_date,'no'=>$claim_no,'amount'=>$claim_amount,'driver_id'=>$claim_driverid,'description'=>$claim_description,'deliveryorder_id'=>$claim_deliveryorderid,'editable'=>0]);
                    }
                }else{
                    $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
                }
            }else{
                $claim = Claim::where('deliveryorder_id',$claim_deliveryorderid)->where('editable',0)->delete();
            }

        }

        return $count;
    }

    public function getDriverLorry(Request $request)
    {
        $data = $request->all();
        $driver_id = $data['driver_id'];
        $result = DB::select('select lorry_id from drivers where id='.$driver_id)[0];
        return $result;
    }

    public function getDriverInfo(Request $request)
    {
        $data = $request->all();
        $driver_id = $data['driver_id'];
        $result = DB::select('select name,ic,grouping,caption from drivers where id='.$driver_id)[0];
        return $result;
    }

    public function getLorryInfo(Request $request)
    {
        $data = $request->all();

        $lorry_id = $data['lorry_id'];
        $vendor_id = $data['vendor_id'];
        $result = DB::select('select l.lorryno,l.type,l.weightagelimit,coalesce(cbv.commissionlimit,l.commissionlimit) commissionlimit,coalesce(cbv.commissionpercentage,l.commissionpercentage) commissionpercentage from lorrys l left join commissionbyvendors cbv on  cbv.lorry_id = l.id and cbv.vendor_id='.$vendor_id.' where l.id='.$lorry_id)[0];
        return $result;
    }

    public function getClaimInfo(Request $request)
    {
        $data = $request->all();

        $dokey = $data['dokey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,no "number",description,format(amount,2) as amount,case when status="1" then "Paid" else "Unpaid" end status from claims where deliveryorder_id='.$dokey);
        return $result;
    }

    public function getTotalAdvance(Request $request)
    {
        $data = $request->all();

        $driverkey = $data['driverkey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,no "number",description,format(amount,2) as amount,case when status="1" then "Paid" else "Unpaid" end status from advances where status = 0 and driver_id='.$driverkey);
        return $result;
    }

    public function getBillingRate(Request $request)
    {
        $data = $request->all();
        $item_id = $data['item_id'];
        $vendor_id = $data['vendor_id'];
        $source_id = $data['source_id'];
        $destinate_id = $data['destinate_id'];
        $result = DB::select('select \'default\' as \'range\', format(i.billingrate,2) as \'billingrate\' from items i where i.id = \''.$item_id.'\' UNION (select concat(p.minrange,\' ~ \',p.maxrange) as \'range\', format(p.billingrate,2) as \'billingrate\' from prices p where p.item_id = \''.$item_id.'\' and p.vendor_id = \''.$vendor_id.'\' and p.source_id = \''.$source_id.'\' and p.destinate_id = \''.$destinate_id.'\' order by p.minrange);');
        return $result;
    }

    public function getCommissionRate(Request $request)
    {
        $data = $request->all();
        $item_id = $data['item_id'];
        $vendor_id = $data['vendor_id'];
        $source_id = $data['source_id'];
        $destinate_id = $data['destinate_id'];
        $result = DB::select('select \'default\' as \'range\', format(i.commissionrate,2) as \'commissionrate\' from items i where i.id = \''.$item_id.'\' UNION (select concat(p.minrange,\' ~ \',p.maxrange) as \'range\', format(p.commissionrate,2) as \'commissionrate\' from prices p where p.item_id = \''.$item_id.'\' and p.vendor_id = \''.$vendor_id.'\' and p.source_id = \''.$source_id.'\' and p.destinate_id = \''.$destinate_id.'\' order by p.minrange);');
        return $result;
    }

    public function getBillingRateInfo(Request $request)
    {
        $data = $request->all();
        $dokey = $data['dokey'];
        $param = DB::select('select dos.item_id, dos.vendor_id, dos.source_id, dos.destinate_id from deliveryorders dos where dos.id = \''.$dokey.'\';');
        if(sizeof($param) == 0){
            return response()->json(['message' => 'Develiery Order not found.'], 500);
        }
        $item_id = $param[0]->item_id;
        $vendor_id = $param[0]->vendor_id;
        $source_id = $param[0]->source_id;
        $destinate_id = $param[0]->destinate_id;
        $result = DB::select('select \'default\' as \'range\', format(i.billingrate,2) as \'billingrate\' from items i where i.id = \''.$item_id.'\' UNION (select concat(p.minrange,\' ~ \',p.maxrange) as \'range\', format(p.billingrate,2) as \'billingrate\' from prices p where p.item_id = \''.$item_id.'\' and p.vendor_id = \''.$vendor_id.'\' and p.source_id = \''.$source_id.'\' and p.destinate_id = \''.$destinate_id.'\' order by p.minrange);');
        return $result;
    }

    public function getCommissionRateInfo(Request $request)
    {
        $data = $request->all();
        $dokey = $data['dokey'];
        $param = DB::select('select dos.item_id, dos.vendor_id, dos.source_id, dos.destinate_id from deliveryorders dos where dos.id = \''.$dokey.'\';');
        if(sizeof($param) == 0){
            return response()->json(['message' => 'Develiery Order not found.'], 500);
        }
        $item_id = $param[0]->item_id;
        $vendor_id = $param[0]->vendor_id;
        $source_id = $param[0]->source_id;
        $destinate_id = $param[0]->destinate_id;
        $result = DB::select('select \'default\' as \'range\', format(i.commissionrate,2) as \'commissionrate\' from items i where i.id = \''.$item_id.'\' UNION (select concat(p.minrange,\' ~ \',p.maxrange) as \'range\', format(p.commissionrate,2) as \'commissionrate\' from prices p where p.item_id = \''.$item_id.'\' and p.vendor_id = \''.$vendor_id.'\' and p.source_id = \''.$source_id.'\' and p.destinate_id = \''.$destinate_id.'\' order by p.minrange);');
        return $result;
    }
}
