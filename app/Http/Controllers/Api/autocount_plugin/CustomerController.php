<?php

namespace App\Http\Controllers\Api\autocount_plugin;

use App\Models\Customer;
use App\Models\StateCode;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    const BATCH_SIZE = 0; // if 0 means no limit

    public function update(Request $request)
    {
        try {
            // Get the payload array we sent from C# (wrapped in a 'debtors' key)
            $process_data = $request->input('debtors', []);

            // Preload lookups once instead of querying per debtor (avoids N+1).
            $stateMap  = StateCode::pluck('id', 'code'); // code => id
            $driverMap = Driver::pluck('id', 'name');    // name => id

            $synced  = 0;
            $skipped = [];

            foreach ($process_data as $index => $customer_data) {

                if (self::BATCH_SIZE > 0) {
                    if ($index >= self::BATCH_SIZE) {
                        break; // Stop processing after reaching the batch size limit
                    }
                }

                if (empty($customer_data['AccNo'])) continue;

                // Isolate each debtor so one bad record can't abort the whole batch.
                try {
                    $addressParts = array_filter([
                        $customer_data['Address1'] ?? null,
                        $customer_data['Address2'] ?? null,
                        $customer_data['Address3'] ?? null,
                        $customer_data['Address4'] ?? null
                    ]);

                    $delivery_address =  array_filter([
                        $customer_data['DeliverAddr1'] ?? null,
                        $customer_data['DeliverAddr2'] ?? null,
                        $customer_data['DeliverAddr3'] ?? null,
                        $customer_data['DeliverAddr4'] ?? null
                    ]);

                    $full_address = implode(', ', $addressParts);
                    $delivery_address = implode(', ', $delivery_address);
                    $status       = (isset($customer_data['IsActive']) && $customer_data['IsActive'] === 'T') ? 1 : 0;

                    // MAPPED: 'ZipCode' is now 'PostCode'. Column is a nullable INT,
                    // so strip any non-numeric characters and store null when empty
                    // (a raw string like "81100 JB" or "" breaks the INT column under
                    // MySQL strict mode and would 500 the whole sync).
                    $digits   = isset($customer_data['PostCode']) ? preg_replace('/\D/', '', $customer_data['PostCode']) : '';
                    $postcode = $digits === '' ? null : (int) $digits;

                    // Core fields (always sourced from the Debtor table).
                    $attributes = [
                        'company'          => $customer_data['CompanyName'] ?? '',
                        //'chinese_name'     => $customer_data['Desc2'] ?? null,
                        'phone'            => $customer_data['Phone1'] ?? null,
                        'billing_address'  => $full_address,
                        'delivery_address' => $delivery_address,
                        'status'           => $status,
                        'registration_no'  => $customer_data['RegisterNo'] ?? null,   // MAPPED: 'BRN' is now 'RegisterNo'
                        'postcode'         => $postcode,
                        'group'            => $customer_data['AreaCode'] ?? null,      // MAPPED: 'Area' is now 'AreaCode'
                        'paymentterm'      => (isset($customer_data['DisplayTerm']) && $customer_data['DisplayTerm'] === 'Cash') ? 'cash' : 'credit_note',
                        'driver_id'        => $driverMap[$customer_data['SalesAgent'] ?? ''] ?? null,
                    ];

                    // e-invoice / TaxEntity fields: only overwrite when the source
                    // actually sends the key. Some AutoCount databases don't expose
                    // these columns, so the plugin omits them - and blindly writing
                    // null here would wipe existing customers' saved e-invoice data.
                    if (array_key_exists('FullTIN', $customer_data) || array_key_exists('TIN', $customer_data)) {
                        $attributes['tin'] = $customer_data['FullTIN'] ?? $customer_data['TIN'] ?? null;
                    }
                    if (array_key_exists('SSTRegisterNo', $customer_data)) {
                        $attributes['sst_registration_no'] = $customer_data['SSTRegisterNo'];
                    }
                    if (array_key_exists('TourismTaxRegisterNo', $customer_data)) {
                        $attributes['tourism_tax_registration'] = $customer_data['TourismTaxRegisterNo'];
                    }
                    if (array_key_exists('MSICCode', $customer_data)) {
                        $attributes['msic'] = $customer_data['MSICCode'];
                    }
                    if (array_key_exists('CountryCode', $customer_data)) {
                        $attributes['country'] = $customer_data['CountryCode'];
                    }
                    if (array_key_exists('StateCode', $customer_data)) {
                        $attributes['state'] = $stateMap[$customer_data['StateCode']] ?? null;   // MAPPED: 'State' is now 'StateCode'
                    }
                    if (array_key_exists('TaxEntityEmail', $customer_data)) {
                        $attributes['email'] = $customer_data['TaxEntityEmail'];
                    }

                    // Use Laravel's updateOrCreate to automatically Insert OR Update
                    Customer::updateOrCreate(['code' => $customer_data['AccNo']], $attributes);

                    $synced++;
                } catch (\Throwable $e) {
                    // Log and skip this debtor; keep processing the rest of the batch.
                    $skipped[] = ['AccNo' => $customer_data['AccNo'], 'error' => $e->getMessage()];
                    Log::error('Customer sync failed for AccNo ' . $customer_data['AccNo'] . ': ' . $e->getMessage());
                }
            }

            // 2. Prepare Success Response
            $responseData = [
                'status'  => 'success',
                'message' => 'Customers synced successfully',
                'synced'  => $synced,
                'skipped' => count($skipped),
                'errors'  => $skipped,
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