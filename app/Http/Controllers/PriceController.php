<?php

namespace App\Http\Controllers;

use App\DataTables\PriceDataTable;
use App\Http\Requests;
use App\Http\Requests\CreatePriceRequest;
use App\Http\Requests\UpdatePriceRequest;
use App\Repositories\PriceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Price;
use App\Models\Item;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PriceController extends AppBaseController
{
    /** @var PriceRepository $priceRepository*/
    private $priceRepository;

    public function __construct(PriceRepository $priceRepo)
    {
        $this->priceRepository = $priceRepo;
    }

    /**
     * Display a listing of the Price.
     *
     * @param PriceDataTable $priceDataTable
     *
     * @return Response
     */
    public function index(PriceDataTable $priceDataTable)
    {
        return $priceDataTable->render('prices.index');
    }

    /**
     * Show the form for creating a new Price.
     *
     * @return Response
     */
    public function create()
    {
        return view('prices.create');
    }

    /**
     * Store a newly created Price in storage.
     *
     * @param CreatePriceRequest $request
     *
     * @return Response
     */
    public function store(CreatePriceRequest $request)
    {
        $input = $request->all();
        
        $data = $request->all();
        $pricelist = Price::where('item_id',$data['item_id'])
        ->where('vendor_id',$data['vendor_id'])
        ->where('source_id',$data['source_id'])
        ->where('destinate_id',$data['destinate_id'])
        ->get()
        ->toarray();
        foreach($pricelist as $pl){

            if($data['minrange'] <= $pl['maxrange'] and $data['minrange'] >= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Min (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

            if($data['maxrange'] <= $pl['maxrange'] and $data['maxrange'] >= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Max (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

            if($data['maxrange'] >= $pl['maxrange'] and $data['minrange'] <= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Min (KG) & Max (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

        }

        $price = $this->priceRepository->create($input);

        Flash::success('Special Price saved successfully.');

        return redirect(route('prices.index'));
    }

    /**
     * Display the specified Price.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $price = $this->priceRepository->find($id);

        if (empty($price)) {
            Flash::error('Special Price not found');

            return redirect(route('prices.index'));
        }

        return view('prices.show')->with('price', $price);
    }

    /**
     * Show the form for editing the specified Price.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $price = $this->priceRepository->find($id);

        if (empty($price)) {
            Flash::error('Special Price not found');

            return redirect(route('prices.index'));
        }

        return view('prices.edit')->with('price', $price);
    }

    /**
     * Update the specified Price in storage.
     *
     * @param int $id
     * @param UpdatePriceRequest $request
     *
     * @return Response
     */
    public function update($id, UpdatePriceRequest $request)
    {
        $id = Crypt::decrypt($id);
        $price = $this->priceRepository->find($id);

        $data = $request->all();
        $pricelist = Price::where('id','<>',$id)
        ->where('item_id',$data['item_id'])
        ->where('vendor_id',$data['vendor_id'])
        ->where('source_id',$data['source_id'])
        ->where('destinate_id',$data['destinate_id'])
        ->get()
        ->toarray();
        foreach($pricelist as $pl){

            if($data['minrange'] <= $pl['maxrange'] and $data['minrange'] >= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Min (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

            if($data['maxrange'] <= $pl['maxrange'] and $data['maxrange'] >= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Max (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

            if($data['maxrange'] >= $pl['maxrange'] and $data['minrange'] <= $pl['minrange']){
                return Redirect::back()->withInput($request->input())->withErrors('Min (KG) & Max (KG) cannot in range ['.$pl['minrange'].'-'.$pl['maxrange'].']');
            }

        }

        if (empty($price)) {
            Flash::error('Special Price not found');

            return redirect(route('prices.index'));
        }

        $price = $this->priceRepository->update($request->all(), $id);

        Flash::success('Special Price updated successfully.');

        return redirect(route('prices.index'));
    }

    /**
     * Remove the specified Price from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $price = $this->priceRepository->find($id);

        if (empty($price)) {
            Flash::error('Special Price not found');

            return redirect(route('prices.index'));
        }

        $this->priceRepository->delete($id);

        Flash::success('Special Price deleted successfully.');

        return redirect(route('prices.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewPrice('.Auth::id().',\'SpecialPrices\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = Price::destroy($ids);

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Price::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }

    public function getBillingRate(Request $request)
    {
        $data = $request->all();
        $vendor_id = $data['vendor_id'];
        $item_id = $data['item_id'];
        $source_id = $data['source_id'];
        $distinate_id = $data['destinate_id'];
        $weight = $data['weight'];
        $billingrate = 0;
        $price = Price::where('vendor_id',$vendor_id)
        ->where('item_id',$item_id)
        ->where('source_id',$source_id)
        ->where('destinate_id',$distinate_id)
        ->where('minrange','<=',$weight)
        ->where('maxrange','>=',$weight)
        ->first();

        if (empty($price)) {
            $billingrate = Item::where('id',$item_id)
            ->first()->billingrate;
        }else{
            $billingrate = $price->billingrate;
        }
        return $billingrate;
    }
}
