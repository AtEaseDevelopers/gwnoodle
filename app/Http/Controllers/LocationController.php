<?php

namespace App\Http\Controllers;

use App\DataTables\LocationDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Repositories\LocationRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Price;
use App\Models\Bonus;
use App\Models\DeliveryOrder;
use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class LocationController extends AppBaseController
{
    /** @var LocationRepository $locationRepository*/
    private $locationRepository;

    public function __construct(LocationRepository $locationRepo)
    {
        $this->locationRepository = $locationRepo;
    }

    /**
     * Display a listing of the Location.
     *
     * @param LocationDataTable $locationDataTable
     *
     * @return Response
     */
    public function index(LocationDataTable $locationDataTable)
    {
        return $locationDataTable->render('locations.index');
    }

    /**
     * Show the form for creating a new Location.
     *
     * @return Response
     */
    public function create()
    {
        return view('locations.create');
    }

    /**
     * Store a newly created Location in storage.
     *
     * @param CreateLocationRequest $request
     *
     * @return Response
     */
    public function store(CreateLocationRequest $request)
    {
        $input = $request->all();

        $location = $this->locationRepository->create($input);

        Flash::success($input['code'].' saved successfully.');

        return redirect(route('locations.index'));
    }

    /**
     * Display the specified Location.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $location = $this->locationRepository->find($id);

        if (empty($location)) {
            Flash::error('Location not found');

            return redirect(route('locations.index'));
        }

        return view('locations.show')->with('location', $location);
    }

    /**
     * Show the form for editing the specified Location.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $location = $this->locationRepository->find($id);

        if (empty($location)) {
            Flash::error('Location not found');

            return redirect(route('locations.index'));
        }

        return view('locations.edit')->with('location', $location);
    }

    /**
     * Update the specified Location in storage.
     *
     * @param int $id
     * @param UpdateLocationRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateLocationRequest $request)
    {
        $id = Crypt::decrypt($id);
        $location = $this->locationRepository->find($id);

        if (empty($location)) {
            Flash::error('Location not found');

            return redirect(route('locations.index'));
        }

        $location = $this->locationRepository->update($request->all(), $id);

        Flash::success($location->code.' updated successfully.');

        return redirect(route('locations.index'));
    }

    /**
     * Remove the specified Location from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $location = $this->locationRepository->find($id);

        if (empty($location)) {
            Flash::error('Location not found');

            return redirect(route('locations.index'));
        }

        $deliveryorder = DeliveryOrder::where('source_id',$id)->get()->toArray();
        if(count($deliveryorder)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Delivery Order');

            return redirect(route('locations.index'));
        }

        $deliveryorder = DeliveryOrder::where('destinate_id',$id)->get()->toArray();
        if(count($deliveryorder)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Delivery Order');

            return redirect(route('locations.index'));
        }

        $price = Price::where('source_id',$id)->get()->toArray();
        if(count($price)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Price');

            return redirect(route('locations.index'));
        }

        $price = Price::where('destinate_id',$id)->get()->toArray();
        if(count($price)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Price');

            return redirect(route('locations.index'));
        }

        $bonus = Bonus::where('source_id',$id)->get()->toArray();
        if(count($bonus)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Bonus');

            return redirect(route('locations.index'));
        }

        $bonus = Bonus::where('destinate_id',$id)->get()->toArray();
        if(count($bonus)>0){
            Flash::error('Unable to delete '.$location->code.', '.$location->code.' is being used in Bonus');

            return redirect(route('locations.index'));
        }

        $this->locationRepository->delete($id);

        Flash::success($location->code.' deleted successfully.');

        return redirect(route('locations.index'));
    }

    public function masssave(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $result = DB::select('CALL spViewLocation('.Auth::id().',\'Locations\',\''.implode(",",$ids).'\')')[0]->ID;

        return $result;
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        
        $count = 0;

        foreach ($ids as $id) {

            $deliveryorder = DeliveryOrder::where('source_id',$id)->get()->toArray();
            if(count($deliveryorder)>0){
                continue;
            }

            $deliveryorder = DeliveryOrder::where('destinate_id',$id)->get()->toArray();
            if(count($deliveryorder)>0){
                continue;
            }
    
            $price = Price::where('source_id',$id)->get()->toArray();
            if(count($price)>0){
                continue;
            }
    
            $price = Price::where('destinate_id',$id)->get()->toArray();
            if(count($price)>0){
                continue;
            }

            $bonus = Bonus::where('source_id',$id)->get()->toArray();
            if(count($bonus)>0){
                continue;
            }

            $bonus = Bonus::where('destinate_id',$id)->get()->toArray();
            if(count($bonus)>0){
                continue;
            }

            $count = $count + Location::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];
        
        $count = Location::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
