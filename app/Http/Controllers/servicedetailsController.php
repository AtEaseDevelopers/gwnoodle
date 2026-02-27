<?php

namespace App\Http\Controllers;

use App\DataTables\servicedetailsDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateservicedetailsRequest;
use App\Http\Requests\UpdateservicedetailsRequest;
use App\Repositories\servicedetailsRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\Code;
use App\Models\servicedetails;
use App\Models\Lorry;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class servicedetailsController extends AppBaseController
{
    /** @var servicedetailsRepository $servicedetailsRepository*/
    private $servicedetailsRepository;

    public function __construct(servicedetailsRepository $servicedetailsRepo)
    {
        $this->servicedetailsRepository = $servicedetailsRepo;
    }

    /**
     * Display a listing of the servicedetails.
     *
     * @param servicedetailsDataTable $servicedetailsDataTable
     *
     * @return Response
     */
    public function index(servicedetailsDataTable $servicedetailsDataTable)
    {
        return $servicedetailsDataTable->render('servicedetails.index');
    }

    /**
     * Show the form for creating a new servicedetails.
     *
     * @return Response
     */
    public function create()
    {
        return view('servicedetails.create');
    }

    /**
     * Store a newly created servicedetails in storage.
     *
     * @param CreateservicedetailsRequest $request
     *
     * @return Response
     */
    public function store(CreateservicedetailsRequest $request)
    {
        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $input['nextdate'] = date_create($input['nextdate']);
        $servicedetails = $this->servicedetailsRepository->create($input);

        $lorry = Lorry::findorfail($servicedetails->lorry_id);
        $body = '<b>Lorry '.$lorry->lorryno.' had added a new service'.'</b><br>-Lorry Number: '.$lorry->lorryno.'<br>-Service Type: '.$servicedetails->type.'<br>-Date: '.$servicedetails->date.'<br>-Next Date: '.$servicedetails->nextdate.'<br>-Amount: '.$servicedetails->amount.'<br>-Remark: '.$servicedetails->remark.'<br><br>';
        $defaultemail = Code::where('code','emailreceiverlorry')->select('value')->get()->toarray();
        foreach($defaultemail as $de){
            $mailstatus = app('App\Http\Controllers\sender')->email('All',$de['value'],'','[Add] Lorry Service ('.$lorry->lorryno.'|'.$servicedetails->type . ') - ' . env('APP_URL'),$body);
        }

        Flash::success(__('lorry_service.lorry_service_saved_successfully'));

        return redirect(route('servicedetails.index'));
    }

    /**
     * Display the specified servicedetails.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $servicedetails = $this->servicedetailsRepository->find($id);

        if (empty($servicedetails)) {
            Flash::error(__('lorry_service.lorry_service_not_found'));

            return redirect(route('servicedetails.index'));
        }

        return view('servicedetails.show')->with('servicedetails', $servicedetails);
    }

    /**
     * Show the form for editing the specified servicedetails.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $servicedetails = $this->servicedetailsRepository->find($id);

        if (empty($servicedetails)) {
            Flash::error(__('lorry_service.lorry_service_not_found'));

            return redirect(route('servicedetails.index'));
        }

        return view('servicedetails.edit')->with('servicedetails', $servicedetails);
    }

    /**
     * Update the specified servicedetails in storage.
     *
     * @param int $id
     * @param UpdateservicedetailsRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateservicedetailsRequest $request)
    {
        $id = Crypt::decrypt($id);
        $servicedetails = $this->servicedetailsRepository->find($id);

        if (empty($servicedetails)) {
            Flash::error(__('lorry_service.lorry_service_not_found'));

            return redirect(route('servicedetails.index'));
        }

        $input = $request->all();

        $input['date'] = date_create($input['date']);
        $input['nextdate'] = date_create($input['nextdate']);
        $servicedetails = $this->servicedetailsRepository->update($input, $id);

        Flash::success(__('lorry_service.lorry_service_updated_successfully'));

        return redirect(route('servicedetails.index'));
    }

    /**
     * Remove the specified servicedetails from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $servicedetails = $this->servicedetailsRepository->find($id);

        if (empty($servicedetails)) {
            Flash::error(__('lorry_service.lorry_service_not_found'));

            return redirect(route('servicedetails.index'));
        }

        $this->servicedetailsRepository->delete($id);

        Flash::success(__('lorry_service.lorry_service_deleted_successfully'));

        return redirect(route('servicedetails.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = servicedetails::destroy($ids);

        return $count;
    }

    public function getTyreServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Tyre" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getInsuranceServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Insurance" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getPermitServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Permit" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getRoadtaxServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Road Tax" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getInspectionServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Inspection" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getOtherServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Other" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }

    public function getFireExtinguisherServiceInfo(Request $request)
    {
        $data = $request->all();

        $lorrykey = $data['lorrykey'];
        $result = DB::select('select DATE_FORMAT(date, "%d-%m-%Y") as date,DATE_FORMAT(nextdate, "%d-%m-%Y") as nextdate,format(amount,2) as amount,coalesce(remark,\'\') as remark from servicedetails where type="Fire Extinguisher" and lorry_id='.$lorrykey.' order by date desc limit 3');
        return $result;
    }
}
