<?php

namespace App\Http\Controllers\Api\autocount_plugin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ApiLog;

class ProductController extends Controller
{
    const BATCH_SIZE = 0; // if 0 means no limit

    // Define allowed prefixes
    const ALLOWED_PREFIXES = ['N', 'OEM', 'GW', 'BK', 'S', 'TD', 'AD'];

    // Define excluded formats: [prefix, suffix]. e.g. starts with 'S' AND ends with 'SM'
    const EXCLUDED_FORMATS = [
        ['S', 'SM'],
    ];

    public function update(Request $request)
    {
        $data = $request->all(); 
        $processedResults = [];

        try {
            if (!is_array($data)) {
                throw new \Exception("Invalid payload format. Expected an array of records.");
            }

            foreach ($data as $index => $record) {
                
                if (self::BATCH_SIZE > 0) {
                    if ($index >= self::BATCH_SIZE) {
                        break;
                    }
                }

                $itemCode = $record['ItemCode'] ?? '';

                // Check if ItemCode matches allowed prefix + has a dash (e.g. OEM123-xxx)
                if (!$this->isAllowedItemCode($itemCode)) {
                    Log::channel('autocount_ignored_products')->info('Ignored product - prefix not allowed', [
                        'item_code'   => $itemCode ?: null,
                        'description' => $record['Description'] ?? null,
                    ]);

                    $processedResults[] = [
                        'unit_code' => $itemCode ?: null,
                        'status'    => 'skipped',
                        'reason'    => 'ItemCode prefix not matched or missing dash.',
                    ];
                    continue; // Skip this record
                }

                $validationErrors = $this->manualValidation($record);

                if (!empty($validationErrors)) {
                    $processedResults[] = [
                        'unit_code' => $record['ItemCode'] ?? null,
                        'status'    => 'validation_error',
                        'errors'    => $validationErrors,
                    ];
                    continue;
                }

                Product::updateOrCreate(
                    ['unit_code' => $record['ItemCode']],
                    [
                        'name'                => $record['Description'] ?? null,
                        'price'               => $record['Price'] ?? 0,
                        'cost'                => $record['Cost'] ?? 0,
                        'uom'                 => $record['UOM'] ?? null,
                        'status'              => !empty($record['IsActive']) ? 1 : 0,
                        'type'                => 1,
                    ]
                );

                $processedResults[] = [
                    'unit_code' => $record['ItemCode'],
                    'status'    => 'processed',
                ];
            }

            $responseData = [
                'status'  => 'success',
                'message' => 'Batch processed successfully.',
                'results' => $processedResults,
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

    /**
     * Check if ItemCode starts with an allowed prefix AND contains a dash.
     * e.g. OEM123-2wqeq-112 ✅ | OEM123 ❌ | RANDOM-123 ❌
     * Excluded formats (e.g. starts with 'S' AND ends with 'SM') are rejected.
     */
    protected function isAllowedItemCode(string $itemCode): bool
    {
        if (empty($itemCode) || !str_contains($itemCode, '-')) {
            return false;
        }

        foreach (self::EXCLUDED_FORMATS as [$prefix, $suffix]) {
            if (str_starts_with($itemCode, $prefix) && str_ends_with($itemCode, $suffix)) {
                return false;
            }
        }

        foreach (self::ALLOWED_PREFIXES as $prefix) {
            if (str_starts_with($itemCode, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function getIgnoredProducts()
    {
        $ignored = Product::select('id', 'name', 'unit_code')
            ->get()
            ->filter(function ($product) {
                return !$this->isAllowedItemCode($product->unit_code ?? '');
            })
            ->values(); // re-index the array
        $no_ignored = Product::select('id', 'name', 'unit_code')
            ->get()
            ->filter(function ($product) {
                return $this->isAllowedItemCode($product->unit_code ?? '');
            })
            ->values(); // re-index the array

        return response()->json([
            'status'  => 'success',
            'total_ignored'   => $ignored->count(),
            'results_ignored' => $ignored,
            'total_no_ignored'   => $no_ignored->count(),
            'results_no_ignored' => $no_ignored,
        ]);
    }

    // protected function isAllowedItemCode(string $itemCode): bool
    // {
    //     if (empty($itemCode)) {
    //         return false;
    //     }

    //     foreach (self::ALLOWED_PREFIXES as $prefix) {
    //         if (str_starts_with($itemCode, $prefix)) {
    //             return true;
    //         }
    //     }

    //     return false;
    // }

    protected function manualValidation(array $record)
    {
        $errors = [];

        if (!isset($record['ItemCode']) || trim($record['ItemCode']) === '') {
            $errors['ItemCode'] = 'The ItemCode field is required and must be a string.';
        }

        if (!array_key_exists('Price', $record) || ($record['Price'] !== null && !is_numeric($record['Price']))) {
            $errors['Price'] = 'The Price field is required and must be a number.';
        }

        if (array_key_exists('Cost', $record) && $record['Cost'] !== null && !is_numeric($record['Cost'])) {
            $errors['Cost'] = 'The Cost field must be a number.';
        }

        if (!isset($record['UOM']) || trim($record['UOM']) === '') {
            $errors['UOM'] = 'The UOM field is required and must be a string.';
        }

        return $errors;
    }
}