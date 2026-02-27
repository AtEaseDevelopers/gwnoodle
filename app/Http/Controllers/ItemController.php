<?php

namespace App\Http\Controllers;

use App\DataTables\ItemDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateItemRequest;
use App\Http\Requests\UpdateItemRequest;
use App\Repositories\ItemRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Price;
use Illuminate\Support\Facades\Redirect;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ItemController extends AppBaseController
{
    /** @var ItemRepository $itemRepository*/
    private $itemRepository;

    public function __construct(ItemRepository $itemRepo)
    {
        $this->itemRepository = $itemRepo;
    }

    /**
     * Display a listing of the Item.
     *
     * @param ItemDataTable $itemDataTable
     *
     * @return Response
     */
    public function index(ItemDataTable $itemDataTable)
    {
        return $itemDataTable->render('items.index');
    }

    /**
     * Show the form for creating a new Item.
     *
     * @return Response
     */
    public function create()
    {
        return view('items.create');
    }

    /**
     * Store a newly created Item in storage.
     *
     * @param CreateItemRequest $request
     *
     * @return Response
     */
    public function store(CreateItemRequest $request)
    {
        $input = $request->all();
        
        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }
        
        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        $item = $this->itemRepository->create($input);

        Flash::success($input['code'].' saved successfully.');

        return redirect(route('items.index'));
    }

    /**
     * Display the specified Item.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $item = $this->itemRepository->find($id);

        if (empty($item)) {
            Flash::error('Item not found');

            return redirect(route('items.index'));
        }

        return view('items.show')->with('item', $item);
    }

    /**
     * Show the form for editing the specified Item.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $item = $this->itemRepository->find($id);

        if (empty($item)) {
            Flash::error('Item not found');

            return redirect(route('items.index'));
        }

        return view('items.edit')->with('item', $item);
    }

    /**
     * Update the specified Item in storage.
     *
     * @param int $id
     * @param UpdateItemRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateItemRequest $request)
    {
        $id = Crypt::decrypt($id);
        $item = $this->itemRepository->find($id);

        if (empty($item)) {
            Flash::error('Item not found');

            return redirect(route('items.index'));
        }

        $input = $request->all();
        
        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }
        
        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        $item = $this->itemRepository->update($input, $id);

        Flash::success($item->code.' updated successfully.');

        return redirect(route('items.index'));
    }

    /**
     * Remove the specified Item from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $item = $this->itemRepository->find($id);

        if (empty($item)) {
            Flash::error('Item not found');

            return redirect(route('items.index'));
        }

        $deliveryorder = DeliveryOrder::where('item_id',$id)->get()->toArray();
        if(count($deliveryorder)>0){
            Flash::error('Unable to delete '.$item->code.', '.$item->code.' is being used in Delivery Order');

            return redirect(route('items.index'));
        }

        $price = Price::where('item_id',$id)->get()->toArray();
        if(count($price)>0){
            Flash::error('Unable to delete '.$item->code.', '.$item->code.' is being used in Price');

            return redirect(route('items.index'));
        }

        $this->itemRepository->delete($id);

        Flash::success($item->code.' deleted successfully.');

        return redirect(route('items.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewItem('.Auth::id().',\'Products\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;

        foreach ($ids as $id) {

            $deliveryorder = DeliveryOrder::where('item_id',$id)->get()->toArray();
            if(count($deliveryorder)>0){
                continue;
            }
    
            $price = Price::where('item_id',$id)->get()->toArray();
            if(count($price)>0){
                continue;
            }

            $count = $count + Item::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Item::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function getBillingRate(Request $request)
    {
        $data = $request->all();
        $item_id = $data['item_id'];
        $item = $this->itemRepository->find($item_id);
        $billingrate = 0;
        if (!empty($item)) {
            $billingrate = $item->billingrate;
        }
        return $billingrate;
    }

    public function getCommissionRate(Request $request)
    {
        $data = $request->all();
        $item_id = $data['item_id'];
        $item = $this->itemRepository->find($item_id);
        $commissionrate = 0;
        if (!empty($item)) {
            $commissionrate = $item->commissionrate;
        }
        return $commissionrate;
    }
}
