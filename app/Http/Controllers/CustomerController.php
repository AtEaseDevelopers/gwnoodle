<?php

namespace App\Http\Controllers;

use App\DataTables\CustomerDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Repositories\CustomerRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\SpecialPrice;
use App\Models\foc;
use App\Models\Assign;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Services\EInvoiceService;
use App\Models\Code; // For dropdown options

class CustomerController extends AppBaseController
{
    /** @var CustomerRepository $customerRepository*/
    private $customerRepository;
    private $eInvoiceService;

    public function __construct(CustomerRepository $customerRepo, EInvoiceService $eInvoiceService)
    {
        $this->customerRepository = $customerRepo;
        $this->eInvoiceService = $eInvoiceService;
    }

    /**
     * Display a listing of the Customer.
     *
     * @param CustomerDataTable $customerDataTable
     *
     * @return Response
     */
    public function index(CustomerDataTable $customerDataTable)
    {
        return $customerDataTable->render('customers.index');
    }

    /**
     * Show the form for creating a new Customer.
     *
     * @return Response
     */
     public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created Customer in storage.
     *
     * @param CreateCustomerRequest $request
     *
     * @return Response
     */
    public function store(CreateCustomerRequest $request)
    {
        $input = $request->all();
        $input['group'] = implode(",",$input['group'] ?? []);
        
        $customer = $this->customerRepository->create($input);

        Flash::success(__('customers.customer_saved_successfully'));

        return redirect(route('customers.index'));
    }

    /**
     * Display the specified Customer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $customer = $this->customerRepository->find($id);

        $customer->group = DB::table('codes')
        ->where('codes.code', '=', 'customer_group')
        ->whereRaw('find_in_set(codes.value, "'.$customer->group.'")')
        ->selectRaw('GROUP_CONCAT(codes.description) as group_descr')
        ->get()->first()->group_descr;
        
        if (empty($customer)) {
            Flash::error(__('customers.customer_not_found'));

            return redirect(route('customers.index'));
        }
        
        return view('customers.show')->with('customer', $customer);
    }

    /**
     * Show the form for editing the specified Customer.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $customer = $this->customerRepository->find($id);

        if (empty($customer)) {
            Flash::error(__('customers.customer_not_found'));

            return redirect(route('customers.index'));
        }
        
        $data = ['customer' => $customer];
    
        return view('customers.edit', $data);
    }

    /**
     * Update the specified Customer in storage.
     *
     * @param int $id
     * @param UpdateCustomerRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateCustomerRequest $request)
    {
        $id = Crypt::decrypt($id);
        $customer = $this->customerRepository->find($id);

        if (empty($customer)) {
            Flash::error(__('customers.customer_not_found'));

            return redirect(route('customers.index'));
        }

        $input = $request->all();
        $input['group'] = implode(",",$input['group'] ?? []);

        $customer = $this->customerRepository->update($input, $id);

        Flash::success(__('customers.customer_updated_successfully'));

        return redirect(route('customers.index'));
    }

    /**
     * Remove the specified Customer from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $customer = $this->customerRepository->find($id);

        if (empty($customer)) {
            Flash::error(__('customers.customer_not_found'));

            return redirect(route('customers.index'));
        }

        $Invoice = Invoice::where('customer_id',$id)->get()->toArray();
        if(count($Invoice)>0){
            Flash::error('Unable to delete '.$customer->name.', '.$customer->name.' is being used in Invoice');

            return redirect(route('customers.index'));
        }

        $SpecialPrice = SpecialPrice::where('customer_id',$id)->get()->toArray();
        if(count($SpecialPrice)>0){
            Flash::error('Unable to delete '.$customer->name.', '.$customer->name.' is being used in Special Price');

            return redirect(route('customers.index'));
        }

        $foc = foc::where('customer_id',$id)->get()->toArray();
        if(count($foc)>0){
            Flash::error('Unable to delete '.$customer->name.', '.$customer->name.' is being used in Foc');

            return redirect(route('customers.index'));
        }

        $Assign = Assign::where('customer_id',$id)->get()->toArray();
        if(count($Assign)>0){
            Flash::error('Unable to delete '.$customer->name.', '.$customer->name.' is being used in Assign');

            return redirect(route('customers.index'));
        }

        $this->customerRepository->delete($id);

        Flash::success($customer->company.__('customers.deleted_successfully'));

        return redirect(route('customers.index'));
    }

    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {

            $Invoice = Invoice::where('customer_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $SpecialPrice = SpecialPrice::where('customer_id',$id)->get()->toArray();
            if(count($SpecialPrice)>0){
                continue;
            }

            $foc = foc::where('customer_id',$id)->get()->toArray();
            if(count($foc)>0){
                continue;
            }

            $Assign = Assign::where('customer_id',$id)->get()->toArray();
            if(count($Assign)>0){
                continue;
            }

            $customer = $this->customerRepository->find($id);

            $count = $count + Customer::destroy($id);
        }

        return $count;
    }

    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Customer::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
    
    public function syncXero(Request $req)
    {
        try {
            $redirect_uri = config('app.url') . '/customers/sync-xero';
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
            $customers = (new CustomerDataTable)->query(new Customer)->whereIn('customers.id', $ids)->get();
            for ($i = 0; $i < count($customers) ;$i++) {
                $res = $xero->createContact($customers[$i]->company);

                if (!$res->ok()) {  
                    throw new Exception('Failed to sync customer.');
                }
            }
            
            Flash::success('Customers synced to Xero.');
            return redirect(route('customers.index'));
        } catch (\Throwable $th) {
            report($th);

            Flash::error('Something went wrong. Please contact administator.');
            return redirect(route('customers.index'));
        }
    }
    
}