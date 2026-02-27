<?php

namespace App\Http\Controllers;

use App\DataTables\AgentDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateAgentRequest;
use App\Http\Requests\UpdateAgentRequest;
use App\Repositories\AgentRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use Illuminate\Support\Facades\File;
use App\Models\Invoice;

class AgentController extends AppBaseController
{
    /** @var AgentRepository $agentRepository*/
    private $agentRepository;

    public function __construct(AgentRepository $agentRepo)
    {
        $this->agentRepository = $agentRepo;
    }

    /**
     * Display a listing of the Agent.
     *
     * @param AgentDataTable $agentDataTable
     *
     * @return Response
     */
    public function index(AgentDataTable $agentDataTable)
    {
        return $agentDataTable->render('agents.index');
    }

    /**
     * Show the form for creating a new Agent.
     *
     * @return Response
     */
    public function create()
    {
        return view('agents.create');
    }

    /**
     * Store a newly created Agent in storage.
     *
     * @param CreateAgentRequest $request
     *
     * @return Response
     */
    public function store(CreateAgentRequest $request)
    {
        $input = $request->all();
        if($input['firstvaccine'] != ''){
            $input['firstvaccine'] = date_create($input['firstvaccine']);
        }
        if($input['secondvaccine'] != ''){
            $input['secondvaccine'] = date_create($input['secondvaccine']);
        }
        $kelindan = $this->agentRepository->create($input);

        Flash::success($input['name'].__('agents.saved_successfully'));

        return redirect(route('agents.index'));
    }

    /**
     * Display the specified Agent.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            Flash::error(__('agents.agent_not_found'));

            return redirect(route('agents.index'));
        }

        return view('agents.show')->with('agent', $agent);
    }

    /**
     * Show the form for editing the specified Agent.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            Flash::error(__('agents.agent_not_found'));

            return redirect(route('agents.index'));
        }

        return view('agents.edit')->with('agent', $agent);
    }

    /**
     * Update the specified Agent in storage.
     *
     * @param int $id
     * @param UpdateAgentRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateAgentRequest $request)
    {
        $id = Crypt::decrypt($id);
        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            Flash::error(__('agents.agent_not_found'));

            return redirect(route('agents.index'));
        }

        $input = $request->all();

        if($input['firstvaccine'] != ''){
            $input['firstvaccine'] = date_create($input['firstvaccine']);
        }
        if($input['secondvaccine'] != ''){
            $input['secondvaccine'] = date_create($input['secondvaccine']);
        }

        $agent = $this->agentRepository->update($input, $id);

        Flash::success(__('agents.agent_updated_successfully'));

        return redirect(route('agents.index'));
    }

    /**
     * Remove the specified Agent from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            Flash::error(__('agents.agent_not_found'));

            return redirect(route('agents.index'));
        }

        $customer = Customer::where('agent_id',$id)->get()->toArray();
        if(count($customer)>0){
            Flash::error('Unable to delete '.$agent->name.', '.$agent->name.' is being used in Customer');

            return redirect(route('agents.index'));
        }

        $Invoice = Invoice::where('agent_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$agent->name.', '.$agent->name.' is being used in Invoice');

            return redirect(route('agents.index'));
        }

        $this->agentRepository->delete($id);

        Flash::success($agent->name.__('agents.deleted_successfully'));

        return redirect(route('agents.index'));
    }

    public function addattachment(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];
        $id = Crypt::decrypt($id);

        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            return response()->json([
                'result' => false,
                'message' => 'Agent not found'
            ], 200);
        }

        if($request->file('attachment') == null){
            return response()->json([
                'result' => false,
                'message' => "Attachment not selected"
            ], 200);
        }else{
            $path = 'assets/upload/agent/attachment/'.uniqid();
            if(!File::isDirectory($path)){
                File::makeDirectory($path, 0777, true, true);
            }
            if($agent->attachment == null){
                $comma = '';
            }else{
                $comma = ',';
            }
            $agent->attachment = $agent->attachment . $comma . $request->file('attachment')->store($path);
            $agent->save();

            return response()->json([
                'result' => true,
                'message' => 'Attachment added'
            ], 200);
        }

    }

    public function getattachment(Request $request)
    {
        $data = $request->all();
        $id = $data['id'];
        $id = Crypt::decrypt($id);

        $agent = $this->agentRepository->find($id);

        if (empty($agent)) {
            return response()->json([
                'result' => false,
                'message' => 'Agent not found'
            ], 200);
        }

        if($agent->attachment == null){
            $attachment = [];
        }else{
            $attachment = explode(',',$agent->attachment);
        }

        return response()->json([
            'result' => true,
            'message' => 'Agent found',
            'data' => $attachment
        ], 200);
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $agent = $this->agentRepository->find($id);

            $customer = Customer::where('agent_id',$id)->get()->toArray();
            if(count($customer)>0){
                continue;
            }

            $Invoice = Invoice::where('agent_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $count = $count + Agent::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Agent::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
}
