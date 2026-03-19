<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryReturnDataTable;
use App\Models\InventoryReturn;
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
use Flash;

class InventoryReturnController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InventoryReturnDataTable $dataTable, Request $request)
    {
        // Get data for filters
        $drivers = Driver::all();
        $products = Product::all();
        $statuses = InventoryReturn::getStatusOptions();
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
        return $dataTable->render('inventory_returns.index', compact('drivers', 'products', 'statuses'));
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
            'items.*.batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        try {
            // Get driver's latest trip
            $latestTrip = Trip::where('driver_id', $request->driver_id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

            if (!$latestTrip) {
                $error = 'No active trip found for this driver';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $error
                    ], 400);
                }
                Flash::error($error);
                return redirect()->back()->withInput();
            }

            $lorryId = $latestTrip->lorry_id;

            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();
            
            if (!$inventoryBalance) {
                $error = 'No inventory found for this driver\'s lorry';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $error
                    ], 400);
                }
                Flash::error($error);
                return redirect()->back()->withInput();
            }

            // Check if driver has enough stock for all items
            $items = $request->items;
            $errors = [];
            
            foreach ($items as $index => $item) {
                $batchId = $item['batch_id'];
                $requestedQty = $item['quantity'];
                
                // Check if batch exists in inventory
                if (!isset($inventoryBalance->batches[$batchId])) {
                    $batch = ProductBatch::find($batchId);
                    $product = Product::find($item['product_id']);
                    $errors[] = ($product->name ?? 'Product') . ' - Batch ' . ($batch->batch_code ?? 'N/A') . ': Not found in driver\'s inventory';
                    continue;
                }
                
                $availableQty = $inventoryBalance->batches[$batchId];
                
                if ($availableQty < $requestedQty) {
                    $batch = ProductBatch::find($batchId);
                    $product = Product::find($item['product_id']);
                    $errors[] = ($product->name ?? 'Product') . ' - Batch ' . ($batch->batch_code ?? 'N/A') . ': Available: ' . $availableQty . ', Requested: ' . $requestedQty;
                }
            }

            if (!empty($errors)) {
                $errorMessage = 'Insufficient stock for some items:<br>' . implode('<br>', $errors);
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }
                Flash::error($errorMessage);
                return redirect()->back()->withInput();
            }

            // Create inventory return with items (auto-approved)
            $inventoryReturn = InventoryReturn::create([
                'driver_id' => $request->driver_id,
                'trip_id' => $latestTrip->id,
                'items' => $items, // Store as JSON array with batch_id and quantity
                'status' => InventoryReturn::STATUS_APPROVED,
                'remarks' => $request->remarks,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Process each item
            foreach ($items as $item) {
                $batchId = $item['batch_id'];
                $quantity = $item['quantity'];
                
                // Update inventory balance (remove from lorry's inventory)
                $inventoryBalance->updateBatchQuantity($batchId, $quantity, 'subtract');
                
                // Get batch details for transaction
                $batch = ProductBatch::find($batchId);
                
                // Create inventory transaction record for STOCK RETURN (negative quantity means stock out from lorry)
                InventoryTransaction::create([
                    'lorry_id' => $lorryId,
                    'product_id' => $item['product_id'],
                    'batch_id' => $batchId,
                    'quantity' => $quantity, 
                    'type' => InventoryTransaction::TYPE_RETURN,
                    'date' => now(),
                    'remarks' => 'Stock return from driver - Batch: ' . ($batch->batch_code ?? 'N/A'),
                    'user' => Auth::user()->name,
                ]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Stock return created successfully.',
                    'data' => $inventoryReturn
                ]);
            }
            
            Flash::success('Stock return created successfully.');
            return redirect(route('inventoryReturns.index'));

        } catch (\Exception $e) {
            \Log::error('Failed to create return: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            $errorMessage = 'Failed to create return: ' . $e->getMessage();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }
            
            Flash::error($errorMessage);
            return redirect()->back()->withInput();
        }
    }

    /**
     * Get return data with batch details for view/edit modal
     */
    public function getReturnWithBatches($id)
    {
        try {
            $inventoryReturn = InventoryReturn::with(['driver', 'approver', 'rejector'])->findOrFail($id);

            // Prepare items with batch details
            $items = [];

            if ($inventoryReturn->items && is_array($inventoryReturn->items)) {
                foreach ($inventoryReturn->items as $item) {
                    $product = Product::find($item['product_id']);
                    $batch = ProductBatch::with('product')->find($item['batch_id']);
                    if (!$product || !$batch) {
                        continue;
                    }

                    $items[] = [
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name,
                        'unit_code' => $product->unit_code,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'returned_quantity' => (int)$item['quantity'],
                        'batch_quantity' => $batch->quantity,
                        'expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon()
                    ];
                }
            }

            // Calculate totals
            $totalReturned = collect($items)->sum('returned_quantity');

            $responseData = [
                'id' => $inventoryReturn->id,
                'driver_id' => $inventoryReturn->driver_id,
                'driver_name' => $inventoryReturn->driver->name ?? 'N/A',
                'trip_id' => $inventoryReturn->trip_id,
                'status' => $inventoryReturn->status,
                'remarks' => $inventoryReturn->remarks,
                'created_at' => $inventoryReturn->created_at ? $inventoryReturn->created_at->format('Y-m-d H:i:s') : null,
                'approved_by' => $inventoryReturn->approver ? $inventoryReturn->approver->name : null,
                'approved_at' => $inventoryReturn->approved_at ? $inventoryReturn->approved_at->format('Y-m-d H:i:s') : null,
                'rejected_by' => $inventoryReturn->rejector ? $inventoryReturn->rejector->name : null,
                'rejected_at' => $inventoryReturn->rejected_at ? $inventoryReturn->rejected_at->format('Y-m-d H:i:s') : null,
                'rejection_reason' => $inventoryReturn->rejection_reason,
                'items' => $items,
                'summary' => [
                    'total_items' => count($items),
                    'total_returned' => $totalReturned,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getReturnWithBatches: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load return details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $inventoryReturn = InventoryReturn::with(['driver', 'approver', 'rejector'])->findOrFail($id);
        // Load product and batch information for each item
        $items = collect($inventoryReturn->items)->map(function($item) {
            $product = Product::find($item['product_id']);
            $batch = ProductBatch::find($item['batch_id']);
            
            return [
                'product_id' => $item['product_id'],
                'product_name' => $product ? $product->name : 'Unknown Product',
                'batch_code' => $batch ? $batch->batch_code : 'Unknown Batch',
                'quantity' => $item['quantity']
            ];
        });

        $inventoryReturn->items = $items;

        return view('inventory_returns.show', compact('inventoryReturn'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $inventoryReturn = InventoryReturn::findOrFail($id);

        // Check if return can be updated - ONLY pending returns
        if ($inventoryReturn->status !== InventoryReturn::STATUS_PENDING) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending returns can be updated.'
                ], 403);
            }
            
            Flash::error('Only pending returns can be updated.');
            return redirect()->back();
        }

        // Validate the request
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $items = $request->items;
            
            // Check for duplicate product+batch combinations
            $combinations = [];
            foreach ($items as $item) {
                $key = $item['product_id'] . '_' . $item['batch_id'];
                if (in_array($key, $combinations)) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Duplicate product and batch combinations are not allowed'
                        ], 422);
                    }
                    
                    Flash::error('Duplicate product and batch combinations are not allowed');
                    return redirect()->back()->withInput();
                }
                $combinations[] = $key;
            }
            
            $updateData = [
                'driver_id' => $request->driver_id,
                'items' => $items,
                'remarks' => $request->remarks,
            ];

            // If save_and_approve flag is set, approve the return
            if ($request->has('save_and_approve') && $request->save_and_approve == '1') {
                $updateData['status'] = InventoryReturn::STATUS_APPROVED;
                $updateData['approved_by'] = Auth::id();
                $updateData['approved_at'] = now();
            }

            $inventoryReturn->update($updateData);

            // If approved, update inventory
            if ($request->has('save_and_approve') && $request->save_and_approve == '1') {
                // Get driver's latest trip to find lorry
                $latestTrip = Trip::where('driver_id', $request->driver_id)
                    ->where('type', 1)
                    ->orderBy('date', 'desc')
                    ->first();

                if ($latestTrip) {
                    $inventoryBalance = InventoryBalance::where('lorry_id', $latestTrip->lorry_id)->first();
                    
                    if ($inventoryBalance) {
                        foreach ($items as $item) {
                            // Update inventory balance
                            $inventoryBalance->updateBatchQuantity($item['batch_id'], $item['quantity'], 'subtract');
                            
                            // Create transaction
                            $batch = ProductBatch::find($item['batch_id']);
                            InventoryTransaction::create([
                                'driver_id' => $request->driver_id,
                                'product_id' => $item['product_id'],
                                'batch_id' => $item['batch_id'],
                                'quantity' => -$item['quantity'],
                                'type' => InventoryTransaction::TYPE_RETURN,
                                'reference_id' => $inventoryReturn->id,
                                'reference_type' => InventoryReturn::class,
                                'remarks' => 'Stock return (approved) - Batch: ' . ($batch->batch_code ?? 'N/A'),
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $request->save_and_approve == '1' ? 
                        'Inventory return approved successfully.' : 
                        'Inventory return updated successfully.',
                    'data' => $inventoryReturn,
                    'redirect' => route('inventoryReturns.index')
                ]);
            }
            
            Flash::success('Inventory return updated successfully.');
            return redirect()->back();

        } catch (\Exception $e) {
            \Log::error('Failed to update return: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update return: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to update return: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Approve the specified return
     */
    public function approve(Request $request, $id)
    {
        $inventoryReturn = InventoryReturn::findOrFail($id);

        // Check if return can be approved
        if ($inventoryReturn->status !== InventoryReturn::STATUS_PENDING) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending returns can be approved.'
                ], 403);
            }
            
            Flash::error('Only pending returns can be approved.');
            return redirect()->back();
        }

        try {
            // Get driver's latest trip
            $latestTrip = Trip::where('driver_id', $inventoryReturn->driver_id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

            if (!$latestTrip) {
                $error = 'No active trip found for this driver';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $error
                    ], 400);
                }
                Flash::error($error);
                return redirect()->back();
            }

            $inventoryBalance = InventoryBalance::where('lorry_id', $latestTrip->lorry_id)->first();
            
            if (!$inventoryBalance) {
                $error = 'No inventory found for this driver\'s lorry';
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $error
                    ], 400);
                }
                Flash::error($error);
                return redirect()->back();
            }

            // Verify stock is still available
            $items = $inventoryReturn->items;
            $errors = [];
            
            foreach ($items as $item) {
                $batchId = $item['batch_id'];
                $requestedQty = $item['quantity'];
                
                if (!isset($inventoryBalance->batches[$batchId])) {
                    $batch = ProductBatch::find($batchId);
                    $product = Product::find($item['product_id']);
                    $errors[] = ($product->name ?? 'Product') . ' - Batch ' . ($batch->batch_code ?? 'N/A') . ': Not found in inventory';
                    continue;
                }
                
                $availableQty = $inventoryBalance->batches[$batchId];
                
                if ($availableQty < $requestedQty) {
                    $batch = ProductBatch::find($batchId);
                    $product = Product::find($item['product_id']);
                    $errors[] = ($product->name ?? 'Product') . ' - Batch ' . ($batch->batch_code ?? 'N/A') . ': Available: ' . $availableQty . ', Requested: ' . $requestedQty;
                }
            }

            if (!empty($errors)) {
                $errorMessage = 'Insufficient stock for some items:<br>' . implode('<br>', $errors);
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 400);
                }
                Flash::error($errorMessage);
                return redirect()->back();
            }

            // Update status
            $inventoryReturn->status = InventoryReturn::STATUS_APPROVED;
            $inventoryReturn->approved_by = Auth::id();
            $inventoryReturn->approved_at = now();
            $inventoryReturn->save();

            // Process each item
            foreach ($items as $item) {
                $batchId = $item['batch_id'];
                $quantity = $item['quantity'];
                
                // Update inventory balance
                $inventoryBalance->updateBatchQuantity($batchId, $quantity, 'subtract');
                
                // Create transaction
                $batch = ProductBatch::find($batchId);
                InventoryTransaction::create([
                    'driver_id' => $inventoryReturn->driver_id,
                    'product_id' => $item['product_id'],
                    'batch_id' => $batchId,
                    'quantity' => -$quantity,
                    'type' => InventoryTransaction::TYPE_RETURN,
                    'reference_id' => $inventoryReturn->id,
                    'reference_type' => InventoryReturn::class,
                    'remarks' => 'Stock return approved - Batch: ' . ($batch->batch_code ?? 'N/A'),
                    'created_by' => Auth::id(),
                ]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory return approved successfully.',
                    'data' => $inventoryReturn
                ]);
            }
            
            Flash::success('Inventory return approved successfully.');
            return redirect()->back();

        } catch (\Exception $e) {
            \Log::error('Failed to approve return: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve return: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to approve return: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Reject the specified return
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $inventoryReturn = InventoryReturn::findOrFail($id);

        // Check if return can be rejected
        if ($inventoryReturn->status !== InventoryReturn::STATUS_PENDING) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending returns can be rejected.'
                ], 403);
            }
            
            Flash::error('Only pending returns can be rejected.');
            return redirect()->back();
        }

        try {
            $inventoryReturn->status = InventoryReturn::STATUS_REJECTED;
            $inventoryReturn->rejected_by = Auth::id();
            $inventoryReturn->rejected_at = now();
            $inventoryReturn->rejection_reason = $request->rejection_reason;
            $inventoryReturn->save();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory return rejected successfully.',
                    'data' => $inventoryReturn
                ]);
            }
            
            Flash::success('Inventory return rejected successfully.');
            return redirect()->back();

        } catch (\Exception $e) {
            \Log::error('Failed to reject return: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject return: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to reject return: ' . $e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inventoryReturn = InventoryReturn::findOrFail($id);

        // Only allow deletion if return is pending
        if ($inventoryReturn->status !== InventoryReturn::STATUS_PENDING) {
            Flash::error('Only pending returns can be deleted.');
            return redirect()->back();
        }

        try {
            $inventoryReturn->delete();
            Flash::success('Inventory return deleted successfully.');
            return redirect(route('inventoryReturns.index'));
        } catch (\Exception $e) {
            Flash::error('Failed to delete return: ' . $e->getMessage());
            return redirect()->back();
        }
    }
    
    /**
     * Get statistics for inventory returns
     */
    public function statistics()
    {
        $total = InventoryReturn::count();
        $pending = InventoryReturn::pending()->count();
        $approved = InventoryReturn::approved()->count();
        $rejected = InventoryReturn::rejected()->count();

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