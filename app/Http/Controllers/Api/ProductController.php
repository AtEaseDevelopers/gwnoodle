<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Product;


class ProductController extends Controller
{
    
    public function sync(Request $request)
    {
        $products = Product::with("category")->select("*");
        $products->where('status', '!=', Product::$status['removed']);

        if($request->input('branch_id')){
            $branchEmail = $request->input('branch_id');
            $products->whereHas('branch', function ($query) use ($branchEmail) {
                $query->where('service_configs', 'LIKE', "%".$branchEmail."%");
            });
        }

        if($filter_category = $request->input('category_id')){
            $products->where('category_id', "$filter_category");
        }
        if($filter_sku = $request->input('sku')){
            $products->where('sku', 'LIKE', "%$filter_sku%");
        }
        if($filter_name = $request->input('name')){
            $products->where('name', 'LIKE', "%$filter_name%");
        }
        if($filter_status = $request->input('status')){
            $products->where('status', $filter_status);
        }
        if($filter_price_range = $request->input('price_range')){
            $filter_price_range = explode(',', $filter_price_range);
            $from_price = $filter_price_range[0];
            $to_price = $filter_price_range[1];
            $products->where('price', '>=', $from_price);
            $products->where('price', '<=', $to_price);
        }

        // $minPrice = Product::min('price');
        // $maxPrice = Product::max('price');

        $products = $products->get();
       
        return $products;
    }

    public function saveProduct(Request $request)
    {
        $data = $request->all(); // Retrieve all product records from the request
        $processedResults = []; // Store results for each record
        try {

            if (!empty($data) && isset($data[0]['CompanyID'])) {
                $companyId = $data[0]['CompanyID'];
                DB::table('products')->where('company_id', $companyId)->update(['status' => 0]);
            }

            foreach ($data as $record) {
                // Perform manual validation for the record
                $validationErrors = $this->manualValidation($record);

                if (!empty($validationErrors)) {
                    // Add validation errors for this record
                    $processedResults[] = [
                        'ItemCode' => $record['ItemCode'] ?? null,
                        'status' => 'validation_error',
                        'errors' => $validationErrors,
                    ];
                    continue; // Skip processing this record
                }

                // Extract validated data
                $validated = [
                    'ItemCode' => $record['ItemCode'],
                    'BalQty' => $record['BalQty'] ?? null,
                    'ItemDetail' => $record['ItemDetail'] ?? null,
                    'Barcode' => $record['Barcode'] ?? null,
                    'ItemGroup' => $record['ItemGroup'] ?? null,
                    'CompanyID' => $record['CompanyID'] ?? null,
                ];

                $category_id = DB::table('categories')->where('name', strtoupper($validated['ItemGroup']))
                ->where('company_id', $validated['CompanyID'])->value('id');

                if (!$category_id) {
                    $category_id = DB::table('categories')->insertGetId([
                        'name' => strtoupper($validated['ItemGroup']),
                        'slug' => strtoupper($validated['ItemGroup']),
                        'company_id' => $validated['CompanyID'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Check if the product exists
                $product = Product::where('item_code', $validated['ItemCode'])
                ->where('company_id', $validated['CompanyID'])
                ->first();

                if ($product) {
                    // Update existing product
                    $product->update([
                        'name' => $validated['ItemDetail'],
                        'description' => $validated['ItemDetail'],
                        'barcode' => $validated['Barcode'],
                        'category_id' => $category_id,
                        'company_id' => $validated['CompanyID'],
                        'sync' => '1',
                        'status' => '1'
                    ]);

                    // $processedResults[] = [
                    //     'ItemCode' => $validated['ItemCode'],
                    //     'status' => 'updated',
                    // ];
                } else {
                    // Create a new product
                    Product::create([
                        'name' => $validated['ItemDetail'],
                        'item_code' => $validated['ItemCode'],
                        'description' => $validated['ItemDetail'],
                        'barcode' => $validated['Barcode'],
                        'company_id' => $validated['CompanyID'],
                        'status' => '1',
                        'category_id' => $category_id,
                        'sync' => '1',
                    ]);

                    // $processedResults[] = [
                    //     'ItemCode' => $validated['ItemCode'],
                    //     'status' => 'created',
                    // ];
                }
            }

            return response()->json([
                'message' => 'Batch processed successfully.',
                'results' => $processedResults,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error processing batch: ' . $e->getMessage()], 500);
        }
    }

    protected function manualValidation(array $record)
    {
        $errors = [];

        if (empty($record['ItemCode']) || !is_string($record['ItemCode'])) {
            $errors['ItemCode'] = 'The ItemCode field is required and must be a string.';
        }

        if (isset($record['BalQty']) && !is_numeric($record['BalQty'])) {
            $errors['BalQty'] = 'The BalQty field must be a numeric value.';
        }

        if (isset($record['ItemDetail']) && !is_string($record['ItemDetail'])) {
            $errors['ItemDetail'] = 'The ItemDetail field must be a string.';
        }

        if (isset($record['Barcode']) && !is_string($record['Barcode'])) {
            $errors['Barcode'] = 'The Barcode field must be a string.';
        }

        if (isset($record['ItemGroup']) && !is_string($record['ItemGroup'])) {
            $errors['ItemGroup'] = 'The ItemGroup field must be a string.';
        }

        if (isset($record['CompanyID']) && !is_string($record['CompanyID'])) {
            $errors['CompanyID'] = 'The CompanyID field must be a string.';
        }

        return $errors;
    }
}
