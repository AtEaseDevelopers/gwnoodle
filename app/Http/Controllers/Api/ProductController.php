<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ApiLog;

class ProductController extends Controller
{
    
    public function update(Request $request)
    {
        // 1. Create the log immediately when the request hits
        $apiLog = ApiLog::createLog($request);

        // Depending on how your C# sends it, you might need $request->input('items', [])
        // If your C# sends a raw array, $request->all() is perfectly fine!
        $data = $request->all(); 
        $processedResults = []; // Store validation errors

        try {
            // Safety check to ensure the payload is actually iterable
            if (!is_array($data)) {
                throw new \Exception("Invalid payload format. Expected an array of records.");
            }

            foreach ($data as $record) {
                $validationErrors = $this->manualValidation($record);

                if (!empty($validationErrors)) {
                    $processedResults[] = [
                        'classification_code' => $record['ItemCode'] ?? null,
                        'status'              => 'validation_error',
                        'errors'              => $validationErrors,
                    ];
                    continue;
                }

                // 2. Clean 'updateOrCreate' logic (Replaces the big if/else block)
                Product::updateOrCreate(
                    ['classification_code' => $record['ItemCode']],
                    [
                        'name'      => $record['Description'] ?? null,
                        'price'     => $record['Price'] ?? 0,
                        'cost'      => $record['Cost'] ?? 0,
                        'unit_code' => $record['UOM'] ?? null,
                        'status'    => !empty($record['IsActive']) ? 1 : 0,
                        'type'      => 1,
                    ]
                );
            }

            // Prepare Success Response
            $responseData = [
                'status'  => 'success',
                'message' => 'Batch processed successfully.',
                'results' => $processedResults, // This will contain any skipped records
            ];
            $statusCode = 200;

        } catch (\Exception $e) {
            $responseData = [
                'status'  => 'error',
                'message' => 'Error processing batch: ' . $e->getMessage(),
                'line'    => $e->getLine()
            ];
            $statusCode = 500;
        }

        $apiLog->updateResponse($responseData, $statusCode);

        return response()->json($responseData, $statusCode);
    }
    
    protected function manualValidation(array $record)
    {
        $errors = [];

        if (empty($record['ItemCode']) || !is_string($record['ItemCode'])) {
            $errors['ItemCode'] = 'The ItemCode field is required and must be a string.';
        }
        if (empty($record['Price']) || !is_numeric($record['Price'])) {
            $errors['Price'] = 'The Price field is required and must be a number.';
        }
        if (empty($record['Cost']) || !is_numeric($record['Cost'])) {
            $errors['Cost'] = 'The Cost field is required and must be a number.';
        }
        if (empty($record['UOM']) || !is_string($record['UOM'])) {
            $errors['UOM'] = 'The UOM field is required and must be a string.';
        }
        return $errors;
    }
}
