<?php

namespace App\Http\Controllers\API;

use App\Agent;
//use App\Models\AgentCustomer;
use App\Helper;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;

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
        $credit_terms_to_payment_method = [
            'C.O.D.' => 'cod',
            'term' => 'term',
        ];
        $process_data = $request->all();

        $default_password = "gsmart12345";
        foreach ($process_data as $key => $customer_data) {
            $user = User::where('api_id', $customer_data['Id'])->where('branch_id', $branch->id)->first();
            
            $full_address = $customer_data['InvoiceAddress']['Address1'].$customer_data['InvoiceAddress']['Address2'].$customer_data['InvoiceAddress']['Address3'].$customer_data['InvoiceAddress']['Address4'];
            if(empty($user)){
                // generate login code for specific user, unique for every user
                do{
                    $login_code = Helper::generateRandomString(100);
                    $exist = User::where('login_code', $login_code)->exists();
                }while($exist);
    
                $user = User::create([
                    'api_account_no' => $customer_data['AccNo'],
                    'api_id' => $customer_data['Id'],
                    'api_data' => json_encode($customer_data),
                    "branch_id" => $branch->id,
                    // "category" => $customer_data['PriceCategory'],
                    "name" => $customer_data['CompanyName'],
                    "email" => null,
                    "attn_name" => "",
                    "attn_contact" => "",
                    "payment_method" => $customer_data['CreditTerm'] == 'C.O.D.'? json_encode([$credit_terms_to_payment_method[$customer_data['CreditTerm']]]) : json_encode([$credit_terms_to_payment_method['term']]),
                    "terms_remark" => $customer_data['CreditTerm'] != 'C.O.D.'? $customer_data['CreditTerm'] : null,
                    "login_code" => $login_code,
                    "phone_no" => $customer_data['InvoiceAddress']['Phone'],
                    "billing_address" => $full_address,
                    "billing_postcode" => "",
                    "billing_state" => "",
                    "shipping_address" => "",
                    "shipping_postcode" => "",
                    "shipping_state" => "",
                    "password" => Hash::make($default_password),
                    'status' => $customer_data['IsActive']? User::$user_status['active'] : User::$user_status['inactive'],
                ]);
            }else{
                $user->update([
                    'api_account_no' => $customer_data['AccNo'],
                    'api_data' => json_encode($customer_data),
                    "branch_id" => $branch->id,
                    // "category" => $customer_data['PriceCategory'],
                    "name" => $customer_data['CompanyName'],
                    "email" => null,
                    "attn_name" => "",
                    "attn_contact" => "",
                    "payment_method" => $customer_data['CreditTerm'] == 'C.O.D.'? json_encode([$credit_terms_to_payment_method[$customer_data['CreditTerm']]]) : json_encode([$credit_terms_to_payment_method['term']]),
                    "terms_remark" => $customer_data['CreditTerm'] != 'C.O.D.'? $customer_data['CreditTerm'] : null,
                    "phone_no" => $customer_data['InvoiceAddress']['Phone'],
                    "billing_address" => $full_address,
                    "billing_postcode" => "",
                    "billing_state" => "",
                    "shipping_address" => "",
                    "shipping_postcode" => "",
                    "shipping_state" => "",
                    "password" => Hash::make($default_password),
                    'status' => $customer_data['IsActive']? User::$user_status['active'] : User::$user_status['inactive'],
                ]);
            }

            if($customer_data['SalesAgent']){
                $agent = Agent::where('branch_id', $branch->id)
                                ->where('name', $customer_data['SalesAgent'])
                                ->first();
            
                if($agent){
                    AgentCustomer::assign_user_to_user($agent, $user);
                }
            }
        }
    }
}
