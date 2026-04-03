<?php

namespace App\Http\Controllers\Api\autocount_plugin;

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

    const BATCH_SIZE = 5; // testing with 5, adjust as needed

    public function update(Request $request)
    {
        // 1. Create the log the second the request hits
        $apiLog = ApiLog::createLog($request);

        try {
            // Get the payload array we sent from C# (wrapped in a 'debtors' key)
            $process_data = $request->input('debtors', []);
            
            foreach ($process_data as $index => $customer_data) {

                if ($index >= self::BATCH_SIZE) {
                    break; // Stop processing after reaching the batch size limit
                }
                
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
                        
                        'paymentterm'              => ($customer_data['DisplayTerm'] === 'Cash') ? 1 : 3, // Assuming 'Cash' = 1, others = 3 (Credit Note) - adjust as needed
                        'state'                    => StateCode::where('state', $customer_data['State'])->value('id') ?? null,
                        'driver_id'                => Agent::where('employeeid', $customer_data['SalesAgent'] ?? null)->value('id') ?? null,
                    ]
                );
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
