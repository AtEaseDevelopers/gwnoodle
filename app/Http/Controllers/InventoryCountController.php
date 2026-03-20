<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryCountDataTable;
use App\Models\InventoryCount;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Lorry;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Warehouse;
use Flash;

class InventoryCountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InventoryCountDataTable $dataTable, Request $request)
    {
        // Get data for filters
        $drivers = Driver::all();
        $products = Product::all();
        $statuses = InventoryCount::getStatusOptions();
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get(); // Add this

        // Pass filter parameters to DataTable
        $dataTable = $dataTable
            ->with([
                'status' => $request->get('status', 'all'),
                'driver_id' => $request->get('driver_id'),
                'product_id' => $request->get('product_id'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
            ]);

        // Return DataTable for AJAX requests
        if ($request->ajax()) {
            return $dataTable->ajax();
        }

        // Return view with DataTable for regular requests
        return $dataTable->render('inventory_counts.index', compact('drivers', 'products', 'statuses','warehouses'));
    }

    /**
     * Get driver's inventory with batches based on latest trip
     */
    public function getDriverInventory(Request $request)
    {
        $driverId = $request->get('driver_id');
        
        if (!$driverId) {
            return response()->json([
                'success' => false,
                'message' => 'Driver ID is required'
            ], 400);
        }
        
        try {
            // Get the driver's latest trip with type 1 (outbound)
            $latestTrip = Trip::where('driver_id', $driverId)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

            if (!$latestTrip) {
                return response()->json([
                    'success' => true,
                    'inventory' => [],
                    'message' => 'No active trip found for this driver'
                ]);
            }

            // Get lorry ID from the trip
            $lorryId = $latestTrip->lorry_id;

            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                return response()->json([
                    'success' => true,
                    'inventory' => [],
                    'message' => 'No inventory found for this driver\'s lorry'
                ]);
            }

            // Get all batch IDs from the inventory
            $batchIds = array_keys($inventoryBalance->batches);

            // Fetch batch details with product information
            $batches = ProductBatch::with('product')
                ->whereIn('id', $batchIds)
                ->where('quantity', '>', 0) // Only batches with stock
                ->where('status', ProductBatch::STATUS_ACTIVE) // Only active batches
                ->get();

            // Prepare inventory data grouped by product
            $inventory = [];
            
            foreach ($batches as $batch) {
                $productId = $batch->product_id;
                $availableQty = $inventoryBalance->batches[$batch->id] ?? 0;
                
                // Skip if no quantity available
                if ($availableQty <= 0) {
                    continue;
                }
                
                // Initialize product entry if not exists
                if (!isset($inventory[$productId])) {
                    $inventory[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $batch->product->name,
                        'product_code' => $batch->product->code,
                        'unit_code' => $batch->product->unit_code,
                        'total_quantity' => 0,
                        'batches' => []
                    ];
                }
                
                // Add batch details
                $inventory[$productId]['batches'][] = [
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $availableQty,
                    'expiry_date' => $batch->expiry_date,
                    'formatted_expiry_date' => $batch->formatted_expiry_date,
                    'is_expiring_soon' => $batch->isExpiringSoon(),
                    'days_to_expiry' => $batch->days_to_expiry
                ];
                
                // Add to total quantity
                $inventory[$productId]['total_quantity'] += $availableQty;
            }

            // Convert to array (reset keys)
            $inventory = array_values($inventory);

            return response()->json([
                'success' => true,
                'inventory' => $inventory,
                'trip_info' => [
                    'trip_id' => $latestTrip->id,
                    'trip_date' => $latestTrip->date,
                    'lorry_id' => $lorryId
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getDriverInventory: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load driver inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product inventory with batch details
     */
    public function getProductInventory($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            // Get all batches for this product
            $batches = ProductBatch::where('product_id', $productId)
                ->where('quantity', '>', 0)
                ->where('status', ProductBatch::STATUS_ACTIVE)
                ->get();
            
            $totalQuantity = $batches->sum('quantity');
            $batchCount = $batches->count();
            
            return response()->json([
                'success' => true,
                'total_quantity' => $totalQuantity,
                'batch_count' => $batchCount,
                'batches' => $batches->map(function($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $batch->quantity,
                        'expiry_date' => $batch->formatted_expiry_date,
                        'is_expiring_soon' => $batch->isExpiringSoon()
                    ];
                })
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error in getProductInventory: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product inventory: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available batches for a specific product from driver's inventory
     */
    public function getProductBatches(Request $request)
    {
        $driverId = $request->get('driver_id');
        $productId = $request->get('product_id');
        
        if (!$driverId || !$productId) {
            return response()->json([
                'success' => false,
                'message' => 'Driver ID and Product ID are required'
            ], 400);
        }
        
        try {
            // Get the driver's latest trip
            $latestTrip = Trip::where('driver_id', $driverId)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

            if (!$latestTrip) {
                return response()->json([
                    'success' => true,
                    'batches' => [],
                    'message' => 'No active trip found for this driver'
                ]);
            }

            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $latestTrip->lorry_id)->first();

            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                return response()->json([
                    'success' => true,
                    'batches' => [],
                    'message' => 'No inventory found'
                ]);
            }

            // Get all batch IDs for this product
            $productBatches = ProductBatch::where('product_id', $productId)
                ->whereIn('id', array_keys($inventoryBalance->batches))
                ->where('quantity', '>', 0)
                ->where('status', ProductBatch::STATUS_ACTIVE)
                ->get();

            $batches = [];
            
            foreach ($productBatches as $batch) {
                $availableQty = $inventoryBalance->batches[$batch->id] ?? 0;
                if ($availableQty > 0) {
                    $batches[] = [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $availableQty,
                        'expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon()
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'batches' => $batches
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getProductBatches: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load product batches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.warehouse_id' => 'required|exists:warehouses,id', // Add warehouse validation
            'remarks' => 'nullable|string|max:500',
        ]);
        
        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $driver = Driver::find($request->driver_id);

        // Get driver's latest trip
        $latestTrip = Trip::where('driver_id', $request->driver_id)
            ->where('type', 1)
            ->orderBy('date', 'desc')
            ->first();

        if (!$latestTrip) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Driver does not have an active trip.'
                ], 422);
            }
            Flash::error('Driver does not have an active trip.');
            return redirect(route('inventoryCounts.index'));
        }

        // Check for existing pending count for this driver and trip
        $existingCount = InventoryCount::where('driver_id', $request->driver_id)
            ->where('trip_id', $latestTrip->id)
            ->where('status', InventoryCount::STATUS_PENDING)
            ->exists();

        if ($existingCount) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A pending inventory count already exists for this driver in the current trip.'
                ], 422);
            }
            Flash::error('A pending inventory count already exists for this driver in the current trip.');
            return redirect(route('inventoryCounts.index'));
        }

        try {
            $formattedItems = [];
            
            // Get inventory balance for current quantities
            $inventoryBalance = InventoryBalance::where('lorry_id', $latestTrip->lorry_id)->first();
            
            foreach ($request->items as $item) {
                $productId = $item['product_id'];
                $countedQuantity = $item['counted_quantity'];
                $warehouseId = $item['warehouse_id']; // Get warehouse ID
                $currentQuantity = 0;
                $batchDetails = [];
                
                // If inventory balance exists, get batch details for this product
                if ($inventoryBalance && !empty($inventoryBalance->batches)) {
                    $batchIds = array_keys($inventoryBalance->batches);
                    $productBatches = ProductBatch::where('product_id', $productId)
                        ->whereIn('id', $batchIds)
                        ->get();
                    
                    foreach ($productBatches as $batch) {
                        $batchQty = $inventoryBalance->batches[$batch->id] ?? 0;
                        if ($batchQty > 0) {
                            $batchDetails[] = [
                                'batch_id' => $batch->id,
                                'batch_code' => $batch->batch_code,
                                'current_quantity' => $batchQty,
                                'counted_quantity' => $countedQuantity,
                                'warehouse_id' => $warehouseId // Add warehouse ID to each batch
                            ];
                            $currentQuantity += $batchQty;
                        }
                    }
                }
                
                // If no batch details found, create a placeholder
                if (empty($batchDetails)) {
                    $batchDetails[] = [
                        'batch_id' => null,
                        'batch_code' => 'N/A',
                        'current_quantity' => 0,
                        'counted_quantity' => $countedQuantity,
                        'warehouse_id' => $warehouseId // Add warehouse ID
                    ];
                }
                
                $formattedItems[] = [
                    'product_id' => $productId,
                    'product_name' => Product::find($productId)->name ?? 'Unknown',
                    'current_quantity' => $currentQuantity,
                    'counted_quantity' => $countedQuantity, // Keep for backward compatibility
                    'warehouse_id' => $warehouseId, // Add warehouse ID at product level
                    'batches' => $batchDetails
                ];
            }
            
            $inventoryCount = InventoryCount::create([
                'driver_id' => $request->driver_id,
                'trip_id' => $latestTrip->id,
                'items' => $formattedItems,
                'status' => InventoryCount::STATUS_PENDING,
                'remarks' => $request->remarks,
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count created successfully with ' . count($request->items) . ' items.',
                    'data' => $inventoryCount
                ]);
            }
            
            Flash::success('Inventory count created successfully with ' . count($request->items) . ' items.');
            return redirect(route('inventoryCounts.index'));
        } catch (\Exception $e) {
            \Log::error('Failed to create count: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to create count: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Get count data with batch details for view/edit modal
     */
    public function getCountWithBatches($id)
    {
        try {
            $inventoryCount = InventoryCount::with(['driver', 'approver', 'rejector'])->findOrFail($id);

            // Get driver's latest trip for lorry info
            $latestTrip = Trip::where('driver_id', $inventoryCount->driver_id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

            $lorryId = $latestTrip ? $latestTrip->lorry_id : null;
            
            // Get current inventory from lorry if available
            $currentInventory = [];
            if ($lorryId) {
                $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();
                if ($inventoryBalance && !empty($inventoryBalance->batches)) {
                    $currentInventory = $inventoryBalance->batches;
                }
            }

            // Prepare items with batch details
            $items = [];
            $varianceCount = 0;
            $countedBatchCount = 0;

            if ($inventoryCount->items && is_array($inventoryCount->items)) {
                foreach ($inventoryCount->items as $item) {
                    $product = Product::find($item['product_id'] ?? null);
                    
                    // Get current quantities from inventory balance if available
                    $batches = $item['batches'] ?? [];
                    $updatedBatches = [];
                    
                    foreach ($batches as $batch) {
                        $batchId = $batch['batch_id'] ?? null;
                        $currentQty = $batch['current_quantity'] ?? 0;
                        
                        // If we have current inventory data, update the current quantity
                        if ($batchId && isset($currentInventory[$batchId])) {
                            $currentQty = $currentInventory[$batchId];
                        }
                        
                        $countedQty = $batch['counted_quantity'] ?? null;
                        $warehouseId = $batch['warehouse_id'] ?? null;
                        
                        // Get warehouse name if exists
                        $warehouseName = null;
                        if ($warehouseId) {
                            $warehouse = Warehouse::find($warehouseId);
                            $warehouseName = $warehouse ? $warehouse->name : null;
                        }
                        
                        // Check if there's a variance
                        if ($countedQty !== null && $countedQty != $currentQty) {
                            $varianceCount++;
                        }
                        
                        // Count batches that have been counted
                        if ($countedQty !== null) {
                            $countedBatchCount++;
                        }
                        
                        // Get batch details
                        $batchDetails = null;
                        if ($batchId) {
                            $batchDetails = ProductBatch::find($batchId);
                        }
                        
                        $updatedBatches[] = [
                            'warehouse_id' => $warehouseId,
                            'warehouse' => $warehouseName ?? ($batch['warehouse'] ?? 'Not specified'),
                            'batch_id' => $batchId,
                            'batch_code' => $batchDetails ? $batchDetails->batch_code : ($batch['batch_code'] ?? 'N/A'),
                            'current_quantity' => $currentQty,
                            'counted_quantity' => $countedQty,
                            'expiry_date' => $batchDetails ? $batchDetails->expiry_date : null,
                            'formatted_expiry_date' => $batchDetails ? $batchDetails->formatted_expiry_date : null,
                            'is_expiring_soon' => $batchDetails ? $batchDetails->isExpiringSoon() : false
                        ];
                    }
                    
                    $items[] = [
                        'product_id' => $item['product_id'],
                        'product_name' => $product ? $product->name : ($item['product_name'] ?? 'Unknown Product'),
                        'unit_code' => $product ? $product->unit_code : null,
                        'batches' => $updatedBatches,
                        'total_current' => collect($updatedBatches)->sum('current_quantity'),
                        'total_counted' => collect($updatedBatches)->sum('counted_quantity')
                    ];
                }
            }

            $responseData = [
                'id' => $inventoryCount->id,
                'driver_id' => $inventoryCount->driver_id,
                'driver_name' => $inventoryCount->driver->name ?? 'N/A',
                'trip_id' => $inventoryCount->trip_id,
                'status' => $inventoryCount->status,
                'remarks' => $inventoryCount->remarks,
                'created_at' => $inventoryCount->created_at ? $inventoryCount->created_at->format('Y-m-d H:i:s') : null,
                'approved_by' => $inventoryCount->approver ? $inventoryCount->approver->name : null,
                'approved_at' => $inventoryCount->approved_at ? $inventoryCount->approved_at->format('Y-m-d H:i:s') : null,
                'rejected_by' => $inventoryCount->rejector ? $inventoryCount->rejector->name : null,
                'rejected_at' => $inventoryCount->rejected_at ? $inventoryCount->rejected_at->format('Y-m-d H:i:s') : null,
                'rejection_reason' => $inventoryCount->rejection_reason,
                'items' => $items,
                'variance_count' => $varianceCount,
                'counted_batch_count' => $countedBatchCount,
                'summary' => [
                    'total_items' => count($items),
                    'total_variance' => $varianceCount,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getCountWithBatches: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load count details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $count = InventoryCount::with(['driver', 'approver', 'rejector'])->findOrFail($id);

        return view('inventory_counts.show', compact('count'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        // Check if count can be updated
        if (!$inventoryCount->canBeUpdated()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This count cannot be updated. It may not be in pending status.'
                ], 422);
            }
            Flash::error('This count cannot be updated.');
            return redirect()->back();
        }

        // Validate request
        $request->validate([
            'batches' => 'required|array',
            'batches.*.batch_id' => 'required|exists:product_batches,id',
            'batches.*.product_id' => 'required|exists:products,id',
            'batches.*.counted_quantity' => 'required|numeric|min:0',
            'batches.*.warehouse_id' => 'required|exists:warehouses,id', // Add warehouse validation
            'remarks' => 'nullable|string|max:500'
        ]);

        try {
            // Get current items
            $items = $inventoryCount->items ?? [];
            $batchesData = $request->input('batches', []);
            
            // Create a lookup for batch data by batch_id
            $batchDataLookup = [];
            foreach ($batchesData as $batchData) {
                $key = $batchData['batch_id'];
                $batchDataLookup[$key] = $batchData;
            }
            
            // Update items with counted quantities and warehouse IDs
            foreach ($items as &$item) {
                if (isset($item['batches']) && is_array($item['batches'])) {
                    foreach ($item['batches'] as &$batch) {
                        $batchId = $batch['batch_id'];
                        
                        if (isset($batchDataLookup[$batchId])) {
                            $batchData = $batchDataLookup[$batchId];
                            $batch['counted_quantity'] = $batchData['counted_quantity'];
                            $batch['warehouse_id'] = $batchData['warehouse_id']; // Add warehouse ID
                            
                            // Add warehouse name for display purposes (optional)
                            if (isset($warehousesLookup[$batchData['warehouse_id']])) {
                                $batch['warehouse_name'] = $warehousesLookup[$batchData['warehouse_id']]['name'];
                            }
                            
                            // Remove from lookup to track processed batches
                            unset($batchDataLookup[$batchId]);
                        }
                    }
                }
            }
            
            // Update remarks
            $inventoryCount->remarks = $request->input('remarks', $inventoryCount->remarks);
            $inventoryCount->items = $items;
            $inventoryCount->save();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock count updated successfully.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Stock count updated successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Failed to update count: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to update count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Approve the specified inventory count and return stock to warehouses.
     */
    public function approve(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        // Check if count can be approved
        if (!$inventoryCount->canBeApproved()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This count cannot be approved. It may not be in pending status.'
                ], 422);
            }
            Flash::error('This count cannot be approved.');
            return redirect()->back();
        }

        try {
            $items = $inventoryCount->items ?? [];
            $missingCountedItems = [];
            $missingWarehouseItems = [];
            $processedBatches = [];
            
            foreach ($items as $item) {
                $batches = $item['batches'] ?? [];
                foreach ($batches as $batch) {
                    $countedQty = $batch['counted_quantity'] ?? null;
                    $warehouseId = $batch['warehouse_id'] ?? null;
                    $productName = $item['product_name'] ?? 'Unknown Product';
                    $batchCode = $batch['batch_code'] ?? 'Unknown Batch';
                    
                    // Check if counted_quantity is empty/null/not set
                    if (empty($countedQty) && $countedQty !== '0' && $countedQty !== 0) {
                        $missingCountedItems[] = $productName . ' (Batch: ' . $batchCode . ')';
                    }
                    
                    // Check if warehouse is selected
                    if (empty($warehouseId)) {
                        $missingWarehouseItems[] = $productName . ' (Batch: ' . $batchCode . ')';
                    }
                    
                    // Store for processing
                    if (!empty($countedQty) && !empty($warehouseId)) {
                        $processedBatches[] = [
                            'batch_id' => $batch['batch_id'],
                            'product_id' => $item['product_id'],
                            'batch_code' => $batchCode,
                            'counted_quantity' => $countedQty,
                            'warehouse_id' => $warehouseId,
                            'current_quantity' => $batch['current_quantity'] ?? 0
                        ];
                    }
                }
            }
            
            // If there are items missing counted_quantity, return error
            if (!empty($missingCountedItems)) {
                $errorMessage = 'Cannot approve. Please fill in counted quantity for: ' . implode(', ', array_slice($missingCountedItems, 0, 5));
                if (count($missingCountedItems) > 5) {
                    $errorMessage .= ' and ' . (count($missingCountedItems) - 5) . ' more';
                }
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
                Flash::error($errorMessage);
                return redirect()->back();
            }
            
            // If there are items missing warehouse, return error
            if (!empty($missingWarehouseItems)) {
                $errorMessage = 'Cannot approve. Please select return warehouse for: ' . implode(', ', array_slice($missingWarehouseItems, 0, 5));
                if (count($missingWarehouseItems) > 5) {
                    $errorMessage .= ' and ' . (count($missingWarehouseItems) - 5) . ' more';
                }
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
                Flash::error($errorMessage);
                return redirect()->back();
            }
            
            // Begin transaction
            \DB::beginTransaction();
            
            try {
                // Process each batch to return stock to warehouse
                foreach ($processedBatches as $batchData) {
                    // Find the product batch
                    $productBatch = \App\Models\ProductBatch::find($batchData['batch_id']);
                    
                    if (!$productBatch) {
                        throw new \Exception('Product batch not found: ' . $batchData['batch_code']);
                    }
                    
                    // Calculate quantity difference
                    $currentQty = $batchData['current_quantity'];
                    $countedQty = $batchData['counted_quantity'];
                    
                    // Create inventory transaction for stock return
                    $transaction = new \App\Models\InventoryTransaction();
                    $transaction->product_id = $batchData['product_id'];
                    $transaction->batch_id = $batchData['batch_id'];
                    $transaction->warehouse_id = $batchData['warehouse_id'];
                    $transaction->quantity = $countedQty; // Positive quantity for stock in
                    $transaction->type = \App\Models\InventoryTransaction::TYPE_STOCK_IN;
                    $transaction->date = now();
                    $transaction->user = Auth::user() ? Auth::user()->name : 'System';
                    $transaction->remark = 'Stock return from Stock Out -[' . $inventoryCount->driver->name. '] - Batch: [' . $batchData['batch_code'].']';
                    $transaction->save();
                    
                }
                
                // Update count status
                $inventoryCount->update([
                    'status' => InventoryCount::STATUS_APPROVED,
                    'approved_by' => Auth::id(),
                    'approved_at' => now(),
                ]);
                
                \DB::commit();
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count approved successfully. Stock has been returned to selected warehouses.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count approved successfully. Stock has been returned to selected warehouses.');
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Failed to approve count: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to approve count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Reject the specified inventory count.
     */
    public function reject(Request $request, $id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);
        
        // Check if count can be rejected
        if (!$inventoryCount->canBeRejected()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This count cannot be rejected. It may not be in pending status.'
                ], 422);
            }
            Flash::error('This count cannot be rejected.');
            return redirect()->back();
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                    'message' => 'Validation failed'
                ], 422);
            }
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $inventoryCount->update([
                'status' => InventoryCount::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory count rejected successfully.',
                    'data' => $inventoryCount
                ]);
            }

            Flash::success('Inventory count rejected successfully.');
            return redirect()->back();
        } catch (\Exception $e) {
            \Log::error('Failed to reject count: ' . $e->getMessage());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject count: ' . $e->getMessage()
                ], 500);
            }
            Flash::error('Failed to reject count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inventoryCount = InventoryCount::findOrFail($id);

        // Only allow deletion if count is pending
        if ($inventoryCount->status !== InventoryCount::STATUS_PENDING) {
            Flash::error('Only pending counts can be deleted.');
            return redirect()->back();
        }

        try {
            $inventoryCount->delete();
            Flash::success('Inventory Count deleted successfully.');
            return redirect(route('inventoryCounts.index'));
        } catch (\Exception $e) {
            Flash::error('Failed to delete count: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Get statistics for inventory requests
     */
    public function statistics()
    {
        $total = InventoryCount::count();
        $pending = InventoryCount::pending()->count();
        $approved = InventoryCount::approved()->count();
        $rejected = InventoryCount::rejected()->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
            ]
        ]);
    }
}