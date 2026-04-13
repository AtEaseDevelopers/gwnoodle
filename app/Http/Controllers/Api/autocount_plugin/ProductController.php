<?php

namespace App\Http\Controllers\Api\autocount_plugin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ApiLog;

class ProductController extends Controller
{
    const BATCH_SIZE = 0; // if 0 means no limit

    public function update(Request $request)
    {
        // Depending on how your C# sends it, you might need $request->input('items', [])
        // If your C# sends a raw array, $request->all() is perfectly fine!
        $data = $request->all(); 
        $processedResults = []; // Store validation errors

        try {
            // Safety check to ensure the payload is actually iterable
            if (!is_array($data)) {
                throw new \Exception("Invalid payload format. Expected an array of records.");
            }

            foreach ($data as $index => $record) {
                
                if (self::BATCH_SIZE > 0) {
                    if ($index >= self::BATCH_SIZE) {
                        break; // Stop processing after reaching the batch size limit
                    }
                }

                $validationErrors = $this->manualValidation($record);

                if (!empty($validationErrors)) {
                    $processedResults[] = [
                        'unit_code' => $record['ItemCode'] ?? null,
                        'status'     => 'validation_error',
                        'errors'     => $validationErrors,
                    ];
                    continue;
                }

                // 2. Clean 'updateOrCreate' logic (Replaces the big if/else block)
                Product::updateOrCreate(
                    ['unit_code' => $record['ItemCode']],
                    [
                        'name'                => $record['Description'] ?? null,
                        'price'               => $record['Price'] ?? 0,
                        'cost'                => $record['Cost'] ?? 0,
                        'classification_code' => $record['UOM'] ?? null,
                        'status'              => !empty($record['IsActive']) ? 1 : 0,
                        'type'                => 1,
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

        return response()->json($responseData, $statusCode);
    }
    
   protected function manualValidation(array $record)
    {
        $errors = [];

        // Check if ItemCode is missing or truly an empty string
        if (!isset($record['ItemCode']) || trim($record['ItemCode']) === '') {
            $errors['ItemCode'] = 'The ItemCode field is required and must be a string.';
        }

        // Allow Price to be 0, but it must be numeric if provided
        if (!array_key_exists('Price', $record) || ($record['Price'] !== null && !is_numeric($record['Price']))) {
            $errors['Price'] = 'The Price field is required and must be a number.';
        }

        // Allow Cost to be null or 0. Only error if it has a weird string value.
        if (array_key_exists('Cost', $record) && $record['Cost'] !== null && !is_numeric($record['Cost'])) {
            $errors['Cost'] = 'The Cost field must be a number.';
        }

        // Check if UOM is missing or empty
        if (!isset($record['UOM']) || trim($record['UOM']) === '') {
            $errors['UOM'] = 'The UOM field is required and must be a string.';
        }

        return $errors;
    }
}
