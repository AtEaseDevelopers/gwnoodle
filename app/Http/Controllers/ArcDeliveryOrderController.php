<?php

namespace App\Http\Controllers;

use App\DataTables\ArcDeliveryOrderDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDeliveryOrderRequest;
use App\Http\Requests\UpdateDeliveryOrderRequest;
use App\Repositories\ArcDeliveryOrderRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Lorry;
use App\Models\CommissionByVendors;
use App\Models\ArcDeliveryOrder;
use Illuminate\Support\Facades\Auth;
use App\Models\Claim;

class ArcDeliveryOrderController extends AppBaseController
{
    /** @var ArcDeliveryOrderRepository $arcdeliveryOrderRepository*/
    private $arcdeliveryOrderRepository;

    public function __construct(ArcDeliveryOrderRepository $arcdeliveryOrderRepo)
    {
        $this->deliveryOrderRepository = $arcdeliveryOrderRepo;
    }

    /**
     * Display a listing of the DeliveryOrder.
     *
     * @param ArcDeliveryOrderDataTable $ArcDeliveryOrderDataTable
     *
     * @return Response
     */
    public function index(ArcDeliveryOrderDataTable $deliveryOrderDataTable)
    {
        return $deliveryOrderDataTable->render('archived.delivery_orders.index');
    }

    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $deliveryOrder = $this->deliveryOrderRepository->find($id);

        if (empty($deliveryOrder)) {
            Flash::error('Delivery Order not found');

            return redirect(url('/archived/deliveryOrders'));
        }

        return view('archived.delivery_orders.show')->with('deliveryOrder', $deliveryOrder);
    }

    public function getClaimInfo(Request $request)
    {
        $data = $request->all();

        $dokey = $data['dokey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,no "number",description,format(amount,2) as amount,case when status="1" then "Paid" else "Unpaid" end status from claims where deliveryorder_id='.$dokey);
        return $result;
    }
}
