<?php

namespace App\Http\Controllers;

use App\DataTables\DriverLocationDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateDriverLocationRequest;
use App\Http\Requests\UpdateDriverLocationRequest;
use App\Repositories\DriverLocationRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\DriverLocation;
use Illuminate\Support\Facades\DB;

class DriverLocationController extends AppBaseController
{
    /** @var DriverLocationRepository $driverLocationRepository*/
    private $driverLocationRepository;

    public function __construct(DriverLocationRepository $driverLocationRepo)
    {
        $this->driverLocationRepository = $driverLocationRepo;
    }

    public function getDriverSummary()
    {
        // [{ lat: 3.1949674484886432, lng: 101.73139214267331 }, "Boynton Pass"],
        $results = array();
        //$DriverLocation = DB::select("select driver_location.id, driver_location.date, driver_location.latitude, driver_location.longitude, driver_location.driver_id, drivers.name as 'driver_name', drivers.employeeid as 'driver_employeeid', kelindans.name as 'kelindan_name', kelindans.employeeid as 'kelindan_employeeid', lorrys.lorryno from driver_location, drivers, kelindans, lorrys, ( select driver_id, max(id) as max_id from driver_location group by driver_id ) max_driver where driver_location.id = max_driver.max_id and drivers.id = driver_location.driver_id and lorrys.id = driver_location.lorry_id and kelindans.id = driver_location.kelindan_id;");
        $DriverLocation = DB::select("select driver_location.id, driver_location.date, driver_location.latitude, driver_location.longitude, driver_location.driver_id, drivers.name as 'driver_name', drivers.employeeid as 'driver_employeeid', lorrys.lorryno from driver_location, drivers,  lorrys, ( select driver_id, max(id) as max_id from driver_location group by driver_id ) max_driver where driver_location.id = max_driver.max_id and drivers.id = driver_location.driver_id and lorrys.id = driver_location.lorry_id;");
        foreach ($DriverLocation as $d){
            $result =  array([
                "lat" => floatval($d->latitude),
                "lng" => floatval($d->longitude),
            ],
            $d->driver_name,
            $d->driver_employeeid,
            //$d->kelindan_employeeid,
            //$d->kelindan_name,
            $d->lorryno,
            $d->date
            );
            array_push($results,$result);
        }
        return $results;
    }

    /**
     * Display a listing of the DriverLocation.
     *
     * @param DriverLocationDataTable $driverLocationDataTable
     *
     * @return Response
     */
    public function index(DriverLocationDataTable $driverLocationDataTable)
    {
        return $driverLocationDataTable->render('driver_locations.index');
    }

    /**
     * Show the form for creating a new DriverLocation.
     *
     * @return Response
     */
    public function create()
    {
        return view('driver_locations.create');
    }

    /**
     * Store a newly created DriverLocation in storage.
     *
     * @param CreateDriverLocationRequest $request
     *
     * @return Response
     */
    public function store(CreateDriverLocationRequest $request)
    {
        $input = $request->all();

        $driverLocation = $this->driverLocationRepository->create($input);

        Flash::success('Driver Location saved successfully.');

        return redirect(route('driverLocations.index'));
    }

    /**
     * Display the specified DriverLocation.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $driverLocation = $this->driverLocationRepository->find($id);

        if (empty($driverLocation)) {
            Flash::error('Driver Location not found');

            return redirect(route('driverLocations.index'));
        }

        return view('driver_locations.show')->with('driverLocation', $driverLocation);
    }

    /**
     * Show the form for editing the specified DriverLocation.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $driverLocation = $this->driverLocationRepository->find($id);

        if (empty($driverLocation)) {
            Flash::error('Driver Location not found');

            return redirect(route('driverLocations.index'));
        }

        return view('driver_locations.edit')->with('driverLocation', $driverLocation);
    }

    /**
     * Update the specified DriverLocation in storage.
     *
     * @param int $id
     * @param UpdateDriverLocationRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateDriverLocationRequest $request)
    {
        $driverLocation = $this->driverLocationRepository->find($id);

        if (empty($driverLocation)) {
            Flash::error('Driver Location not found');

            return redirect(route('driverLocations.index'));
        }

        $driverLocation = $this->driverLocationRepository->update($request->all(), $id);

        Flash::success('Driver Location updated successfully.');

        return redirect(route('driverLocations.index'));
    }

    /**
     * Remove the specified DriverLocation from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $driverLocation = $this->driverLocationRepository->find($id);

        if (empty($driverLocation)) {
            Flash::error('Driver Location not found');

            return redirect(route('driverLocations.index'));
        }

        $this->driverLocationRepository->delete($id);

        Flash::success('Driver Location deleted successfully.');

        return redirect(route('driverLocations.index'));
    }
}
