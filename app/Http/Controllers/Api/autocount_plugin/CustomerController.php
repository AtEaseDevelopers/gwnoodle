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
    const BATCH_SIZE = 0; // if 0 means no limit

    public function update(Request $request)
    {
        try {
            // Get the payload array we sent from C# (wrapped in a 'debtors' key)
            $process_data = $request->input('debtors', []);
            
            foreach ($process_data as $index => $customer_data) {

                if (self::BATCH_SIZE > 0) {
                    if ($index >= self::BATCH_SIZE) {
                        break; // Stop processing after reaching the batch size limit
                    }
                }
                
                if (empty($customer_data['AccNo'])) continue;

                $addressParts = array_filter([
                    $customer_data['Address1'] ?? null,
                    $customer_data['Address2'] ?? null,
                    $customer_data['Address3'] ?? null,
                    $customer_data['Address4'] ?? null
                ]);

                $full_address = implode(', ', $addressParts);
                $status       = (isset($customer_data['IsActive']) && $customer_data['IsActive'] === 'T') ? 1 : 0;
            
                // MAPPED: 'ZipCode' is now 'PostCode'
                $postcode = isset($customer_data['PostCode']) ? (int)preg_replace('/\D/', '', $customer_data['PostCode']) : 0;

                // Use Laravel's updateOrCreate to automatically Insert OR Update
                $customer = Customer::updateOrCreate(
                    ['code' => $customer_data['AccNo']], 
                    [
                        'company'                  => $customer_data['CompanyName'] ?? '',
                        //'chinese_name'             => $customer_data['Desc2'] ?? null, 
                        'phone'                    => $customer_data['Phone1'] ?? null,
                        'address'                  => $full_address,
                        'status'                   => $status,
                        'tin'                      => $customer_data['TIN'] ?? null,                       
                        'sst_registration_no'      => $customer_data['SSTRegisterNo'] ?? null,        // MAPPED: 'TaxRegNo' is now 'SSTRegisterNo' (or could use GSTRegisterNo)
                        'registration_no'          => $customer_data['RegisterNo'] ?? null,           // MAPPED: 'BRN' is now 'RegisterNo' 
                        'tourism_tax_registration' => $customer_data['TourismTaxRegisterNo'] ?? null, // MAPPED: 'TourismTaxRegNo' is now 'TourismTaxRegisterNo'                        
                        'msic'                     => $customer_data['MSICCode'] ?? null,             // MAPPED: 'MSIC' is now 'MSICCode'
                        'city'                     => null,
                        'country'                  => $customer_data['CountryCode'] ?? null, 
                        'postcode'                 => $postcode,
                        'email'                    => $customer_data['EmailAddress'] ?? null,
                        'group'                    => $customer_data['AreaCode'] ?? null, // MAPPED: 'Area' is now 'AreaCode'
                        'paymentterm'              => (isset($customer_data['DisplayTerm']) && $customer_data['DisplayTerm'] === 'Cash') ? 1 : 3, 
                        'state'                    => StateCode::where('state', $customer_data['StateCode'] ?? '')->value('id') ?? null,  // MAPPED: 'State' is now 'StateCode'
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

        return response()->json($responseData, $statusCode);
    }
}