<?php

namespace App\Http\Controllers\API;

use App\Models\Agent;
use App\Models\AgentCustomer;
use App\Models\Customer;
use App\Models\StateCode;
use App\Models\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ApiLog;
//use App\Models\Branch;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function sync(Request $request)
    {
        $users = User::select("users.*", 'branches.label as branch_label', 'agents.id as agent_id', 'agents.username as agent_username', 'agents.name as agent_name');
        $users->leftJoin('branches', 'branches.id', '=', 'users.branch_id');
        $users->leftJoin('agent_customers', 'agent_customers.user_id', '=', 'users.id');
        $users->leftJoin('agents', 'agents.id', '=', 'agent_customers.agent_id');
        $users->where('users.status', '!=', User::$user_status['removed']);

        if($request->input('branch_id')){
            $branchEmail = $request->input('branch_id');
            $users->whereHas('branch', function ($query) use ($branchEmail) {
                $query->where('service_configs', 'LIKE', "%".$branchEmail."%");
            });
        }
        if($filter_agent = $request->input('agent_id')){
            $users->where('agent_customers.agent_id', $filter_agent);
        }
       // $users->where('agent_customers.status', AgentCustomer::$status['active']);

        if($filter_name = $request->input('name')){
            $users->where('users.name', 'LIKE', "%$filter_name%");
        }
        if($filter_email = $request->input('email')){
            $users->where('users.email', 'LIKE', "%$filter_email%");
        }
        if($filter_category = $request->input('category')){
            $users->where('users.category', $filter_category);
        }
        if($filter_shipping_state = $request->input('shipping_state')){
            $users->where('users.shipping_state', $filter_shipping_state);
        }
        if($filter_status = $request->input('status')){
            $users->where('users.status', $filter_status);
        }

        $users = $users->get();

        return $users;
    }
    
    public function update1(Request $request)
    {
        $data = $request->input();
        
        Log::error(json_encode($data));
        
        $customer = User::where("name", $data['CompanyName']);
        
        if($customer)
        {
            $customer->update([
            'api_account_no' => $data['AccNo'],
            'api_id' => '{[!MzAwLTAwMDE!]}',
            'api_data' =>json_encode($data),
            ]);
        }
        else
        {
            return $data;
        }

        return "OK";
    }
    
    public function update(Request $request)
    {
        // 1. Create the log the second the request hits
        $apiLog = ApiLog::createLog($request);

        try {
            // Get the payload array we sent from C# (wrapped in a 'debtors' key)
            $process_data = $request->input('debtors', []);
            
            foreach ($process_data as $customer_data) {
                
                if (empty($customer_data['AccNo'])) continue;

                $addressParts = array_filter([
                    $customer_data['Address1'] ?? null,
                    $customer_data['Address2'] ?? null,
                    $customer_data['Address3'] ?? null,
                    $customer_data['Address4'] ?? null
                ]);
                $full_address = implode(', ', $addressParts);

                $status   = (isset($customer_data['IsActive']) && $customer_data['IsActive'] === 'T') ? 1 : 0;
                $postcode = isset($customer_data['ZipCode']) ? (int)preg_replace('/\D/', '', $customer_data['ZipCode']) : 0;

                // Use Laravel's updateOrCreate to automatically Insert OR Update
                $customer = Customer::updateOrCreate(
                    ['code' => $customer_data['AccNo']], 
                    [
                        'company'                  => $customer_data['CompanyName'] ?? '',
                        'customer_type'            => $customer_data['DebtorType'] ?? null,
                        'chinese_name'             => $customer_data['Name2'] ?? null,
                        'phone'                    => $customer_data['Phone1'] ?? null,
                        'address'                  => $full_address,
                        'status'                   => $status,
                        'tin'                      => $customer_data['TIN'] ?? null,
                        'sst_registration_no'      => $customer_data['TaxRegNo'] ?? null,
                        'registration_no'          => $customer_data['BRN'] ?? null,
                        'tourism_tax_registration' => $customer_data['TourismTaxRegNo'] ?? null,
                        'msic'                     => $customer_data['MSIC'] ?? null,
                        'city'                     => $customer_data['City'] ?? null,
                        'country'                  => $customer_data['Country'] ?? null,
                        'postcode'                 => $postcode,
                        'email'                    => $customer_data['EmailAddress'] ?? null,
                        'group'                    => $customer_data['Area'] ?? null,
                        
                        'paymentterm'              => null,
                        'state'                    => StateCode::where('state', $customer_data['State'])->value('id') ?? null,
                        'agent_id'                 => Agent::where('employeeid', $customer_data['SalesAgent'] ?? null)->value('id') ?? null,
                        'supervisor_id'            => Agent::where('employeeid', $customer_data['Supervisor'] ?? null)->value('id') ?? null,
                        'driver_id'                => Agent::where('employeeid', $customer_data['Driver'] ?? null)->value('id') ?? null,
                    ]
                );

                // (Optional) Handle Agent Assignment if needed
                /*
                if (!empty($customer_data['SalesAgent'])) {
                    $agent = Agent::where('name', $customer_data['SalesAgent'])->first();
                    if ($agent) {
                        // Assign agent logic...
                    }
                }
                */
            }

            // 2. Prepare Success Response
            $responseData = [
                'status'  => 'success',
                'message' => 'Customers synced successfully'
            ];
            $statusCode = 200;

        } catch (\Exception $e) {
            
            // 3. Prepare Error Response if something breaks (e.g., DB connection fails)
            $responseData = [
                'status'  => 'error',
                'message' => 'Error syncing customers: ' . $e->getMessage(),
                'line'    => $e->getLine()
            ];
            $statusCode = 500;
        }

        // 4. Update the exact same log record with the final response payload and status
        $apiLog->updateResponse($responseData, $statusCode);

        return response()->json($responseData, $statusCode);
    }
}
