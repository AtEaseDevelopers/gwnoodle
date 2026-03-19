<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryRequestDataTable;
use App\Models\InventoryRequest;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Driver;
use App\Models\ProductBatch;
use App\Models\InventoryTransaction;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Flash;

class InventoryRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(InventoryRequestDataTable $dataTable, Request $request)
    {
        // Get data for filters
        $drivers = Driver::all();
        $products = Product::where('status',1)->get();

        $statuses = InventoryRequest::getStatusOptions();
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
        return $dataTable->render('inventory_requests.index', compact('drivers', 'products', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), InventoryRequest::$rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate items array
            $items = $request->items;
            if (!is_array($items) || empty($items)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please add at least one item'
                ], 422);
            }

            // Check for duplicate product+batch combinations
            $productBatchPairs = array_map(function($item) {
                return $item['product_id'] . '-' . $item['batch_id'];
            }, $items);
            
            if (count($productBatchPairs) !== count(array_unique($productBatchPairs))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Duplicate product with same batch is not allowed in the same request'
                ], 422);
            }

            // Validate each batch exists and has enough quantity
            foreach ($items as $item) {
                $batch = ProductBatch::find($item['batch_id']);
                if (!$batch) {
                    return response()->json([
                        'success' => false,
                        'message' => "Batch ID {$item['batch_id']} not found"
                    ], 422);
                }
                
                if ($batch->product_id != $item['product_id']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Batch does not belong to the selected product"
                    ], 422);
                }
                
                if ($item['quantity'] > $batch->quantity) {
                    return response()->json([
                        'success' => false,
                        'message' => "Requested quantity for batch {$batch->batch_code} exceeds available stock ({$batch->quantity})"
                    ], 422);
                }
            }

            // Create inventory request with items
            $inventoryRequest = InventoryRequest::create([
                'driver_id' => $request->driver_id,
                'items' => $items, // Store as JSON array
                'status' => InventoryRequest::STATUS_PENDING,
                'remarks' => $request->remarks,
            ]);

            // Return success response (will be handled by AJAX)
            return response()->json([
                'success' => true,
                'message' => 'Inventory request created successfully.',
                'data' => $inventoryRequest
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $request = InventoryRequest::with(['driver', 'approver', 'rejector'])->findOrFail($id);

        // Load product and batch information for each item
        $items = collect($request->items)->map(function($item) {
            $product = Product::find($item['product_id']);
            $batch = ProductBatch::find($item['batch_id']);
            
            return [
                'product_id' => $item['product_id'],
                'product_name' => $product ? $product->name : 'Unknown Product',
                'batch_id' => $item['batch_id'],
                'batch_code' => $batch ? $batch->batch_code : 'Unknown Batch',
                'quantity' => $item['quantity'],
                'expiry_date' => $batch ? $batch->formatted_expiry_date : null,
            ];
        });

        $request->items = $items;

        return view('inventory_requests.show', compact('request'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $inventoryRequest = InventoryRequest::findOrFail($id);

        // Check if request can be updated - ONLY pending requests
        if ($inventoryRequest->status !== InventoryRequest::STATUS_PENDING) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only pending requests can be updated.'
                ], 403);
            }
            
            Flash::error('Only pending requests can be updated.');
            return redirect()->route('inventoryRequests.index');
        }

        $validator = Validator::make($request->all(), InventoryRequest::$rules);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            // Update with new items array
            $items = $request->items;
            
            // Check for duplicate product+batch combinations
            $productBatchPairs = array_map(function($item) {
                return $item['product_id'] . '-' . $item['batch_id'];
            }, $items);
            
            if (count($productBatchPairs) !== count(array_unique($productBatchPairs))) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duplicate product with same batch is not allowed in the same request'
                    ], 422);
                }
                
                Flash::error('Duplicate product with same batch is not allowed in the same request');
                return redirect()->route('inventoryRequests.index');
            }

            // Validate each batch exists and has enough quantity
            foreach ($items as $item) {
                $batch = ProductBatch::find($item['batch_id']);
                if (!$batch) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Batch ID {$item['batch_id']} not found"
                        ], 422);
                    }
                    
                    Flash::error("Batch ID {$item['batch_id']} not found");
                    return redirect()->route('inventoryRequests.index');
                }
                
                if ($batch->product_id != $item['product_id']) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Batch does not belong to the selected product"
                        ], 422);
                    }
                    
                    Flash::error("Batch does not belong to the selected product");
                    return redirect()->route('inventoryRequests.index');
                }
                
                if ($item['quantity'] > $batch->quantity) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Requested quantity for batch {$batch->batch_code} exceeds available stock ({$batch->quantity})"
                        ], 422);
                    }
                    
                    Flash::error("Requested quantity for batch {$batch->batch_code} exceeds available stock ({$batch->quantity})");
                    return redirect()->route('inventoryRequests.index');
                }
            }

            $updateData = [
                'driver_id' => $request->driver_id,
                'items' => $items,
                'remarks' => $request->remarks,
            ];

            // Update the request
            $inventoryRequest->update($updateData);
            
            // Check if we need to save and approve
            if ($request->has('save_and_approve') && $request->save_and_approve == '1') {
                // Use the approve logic
                return $this->approve($request, $id);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory request updated successfully.',
                    'data' => $inventoryRequest,
                    'redirect' => route('inventoryRequests.index')
                ]);
            }
            
            Flash::success('Inventory request updated successfully.');
            return redirect()->route('inventoryRequests.index');
            
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update request: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to update request: ' . $e->getMessage());
            return redirect()->route('inventoryRequests.index');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $inventoryRequest = InventoryRequest::findOrFail($id);

        // Only allow deletion if request is pending
        if ($inventoryRequest->status !== InventoryRequest::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Only pending requests can be deleted.'
            ], 403);
        }

        try {
            $inventoryRequest->delete();

            Flash::success('Inventory Request deleted successfully.');
            return redirect(route('inventoryRequests.index'));

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get request data with batch details for editing and viewing
     */
    public function getRequestWithBatches($id)
    {
        try {
            $inventoryRequest = InventoryRequest::with(['driver', 'approver', 'rejector'])->findOrFail($id);

            // Prepare items with batch details
            $items = [];

            if ($inventoryRequest->items && is_array($inventoryRequest->items)) {
                foreach ($inventoryRequest->items as $item) {
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
                        'requested_quantity' => (int)$item['quantity'],
                        'batch_quantity' => $batch->quantity,
                        'expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon()
                    ];
                }
            }

            // Calculate totals
            $totalRequested = collect($items)->sum('requested_quantity');

            $responseData = [
                'id' => $inventoryRequest->id,
                'driver_id' => $inventoryRequest->driver_id,
                'driver_name' => $inventoryRequest->driver->name ?? 'N/A',
                'status' => $inventoryRequest->status,
                'remarks' => $inventoryRequest->remarks,
                'created_at' => $inventoryRequest->created_at ? $inventoryRequest->created_at->format('Y-m-d H:i:s') : null,
                'approved_by' => $inventoryRequest->approver ? $inventoryRequest->approver->name : null,
                'approved_at' => $inventoryRequest->approved_at ? $inventoryRequest->approved_at->format('Y-m-d H:i:s') : null,
                'rejected_by' => $inventoryRequest->rejector ? $inventoryRequest->rejector->name : null,
                'rejected_at' => $inventoryRequest->rejected_at ? $inventoryRequest->rejected_at->format('Y-m-d H:i:s') : null,
                'rejection_reason' => $inventoryRequest->rejection_reason,
                'items' => $items,
                'summary' => [
                    'total_items' => count($items),
                    'total_requested' => $totalRequested,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $responseData
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getRequestWithBatches: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load request details: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve the specified inventory request.
     */
    public function approve(Request $request, $id)
    {
        $inventoryRequest = InventoryRequest::findOrFail($id);

        // Check if request can be approved
        if (!$inventoryRequest->canBeApproved()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be approved.'
                ], 403);
            }
            
            Flash::error('This request cannot be approved.');
            return redirect()->route('inventoryRequests.index');
        }

        try {
            // Check if items exist
            $items = $inventoryRequest->items;
            if (empty($items) || !is_array($items)) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No items found in this request.'
                    ], 422);
                }
                
                Flash::error('No items found in this request.');
                return redirect()->route('inventoryRequests.index');
            }

            // Get the latest trip for this driver to find the lorry_id
            $latestTrip = Trip::where('driver_id', $inventoryRequest->driver_id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();
                
            if (!$latestTrip) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No active trip found for this driver.'
                    ], 422);
                }
                
                Flash::error('No active trip found for this driver.');
                return redirect()->route('inventoryRequests.index');
            }

            $lorry_id = $latestTrip->lorry_id;

            // Get or create inventory balance for this lorry
            $inventoryBalance = InventoryBalance::firstOrNew([
                'lorry_id' => $lorry_id
            ]);

            // If new record, initialize with empty batches array
            if (!$inventoryBalance->exists) {
                $inventoryBalance->batches = [];
            }

            // Process each item (each item represents a batch allocation)
            foreach ($items as $item) {
                $batchId = $item['batch_id'];
                $quantity = $item['quantity'];
                $productId = $item['product_id'];
                // Find the batch
                $batch = ProductBatch::findOrFail($batchId);

                // Check if batch has enough quantity
                if ($quantity > $batch->quantity) {
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => "Batch {$batch->batch_code} only has {$batch->quantity} available, but {$quantity} requested."
                        ], 422);
                    }
                    
                    Flash::error("Batch {$batch->batch_code} only has {$batch->quantity} available, but {$quantity} requested.");
                    return redirect()->route('inventoryRequests.index');
                }

                // Reduce quantity from warehouse batch
                $batch->quantity -= $quantity;
                $batch->save();

                // Add to driver's lorry inventory
                $inventoryBalance->updateBatchQuantity($batchId, $quantity, 'add');

                // Create inventory transaction record for STOCK OUT from warehouse
                InventoryTransaction::create([
                    'type' => InventoryTransaction::TYPE_STOCK_OUT,
                    'batch_id' => $batchId,
                    'quantity' => -$quantity,
                    'date' => now(),
                    'remark' => "Stock Request #{$inventoryRequest->id} - Transfer to Driver (ID: {$inventoryRequest->driver->name})",
                    'user' => Auth::user()->name,
                    'lorry_id' => $lorry_id,
                    'product_id' => $productId,
                ]);

                // // Create inventory transaction record for STOCK IN to driver's lorry
                // InventoryTransaction::create([
                //     'type' => InventoryTransaction::TYPE_STOCK_IN,
                //     'lorry_id' => $lorry_id,
                //     'product_id' => $productId,
                //     'batch_id' => $batchId,
                //     'quantity' => $quantity,
                //     'date' => now(),
                //     'remark' => "Stock Request #{$inventoryRequest->id} - Received from Warehouse",
                //     'user' => Auth::user()->name,
                // ]);
            }

            // Save the inventory balance
            $inventoryBalance->save();

            // Update request status
            $inventoryRequest->update([
                'status' => InventoryRequest::STATUS_APPROVED,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory request approved successfully. Quantities added to driver inventory.',
                    'redirect' => route('inventoryRequests.index')
                ]);
            }
            
            Flash::success('Inventory request approved successfully. Quantities added to driver inventory.');
            return redirect()->route('inventoryRequests.index');
            
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to approve request: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to approve request: ' . $e->getMessage());
            return redirect()->route('inventoryRequests.index');
        }
    }

    public function getNotificationCounts()
    {
        return response()->json([
            'pendingStockRequests' => \App\Models\InventoryRequest::where('status', \App\Models\InventoryRequest::STATUS_PENDING)->count(),
            'pendingStockCounts' => \App\Models\InventoryCount::where('status', \App\Models\InventoryCount::STATUS_PENDING)->count(),
        ]);
    }

    /**
     * Reject the specified inventory request.
     */
    public function reject(Request $request, $id)
    {
        $inventoryRequest = InventoryRequest::findOrFail($id);
        
        // Check if request can be rejected
        if (!$inventoryRequest->canBeRejected()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This request cannot be rejected.'
                ], 403);
            }
            
            Flash::error('This request cannot be rejected.');
            return redirect()->route('inventoryRequests.index');
        }

        $validator = Validator::make($request->all(), [
            'rejection_reason' => 'required|string|min:5|max:500'
        ]);

        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $inventoryRequest->update([
                'status' => InventoryRequest::STATUS_REJECTED,
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'rejected_at' => now(),
            ]);

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Inventory request rejected successfully.',
                    'redirect' => route('inventoryRequests.index')
                ]);
            }
            
            Flash::success('Inventory request rejected successfully.');
            return redirect()->route('inventoryRequests.index');
            
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to reject request: ' . $e->getMessage()
                ], 500);
            }
            
            Flash::error('Failed to reject request: ' . $e->getMessage());
            return redirect()->route('inventoryRequests.index');
        }
    }

    /**
     * Get statistics for inventory requests
     */
    public function statistics()
    {
        $total = InventoryRequest::count();
        $pending = InventoryRequest::pending()->count();
        $approved = InventoryRequest::approved()->count();
        $rejected = InventoryRequest::rejected()->count();

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

    /**
     * Get request data for AJAX operations (for view modal)
     */
    public function getRequestData($id)
    {
        $request = InventoryRequest::with(['driver', 'approver', 'rejector'])->findOrFail($id);

        // Prepare items with product and batch names
        $items = [];
        if ($request->items && is_array($request->items)) {
            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                $batch = ProductBatch::find($item['batch_id']);
                $items[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $product ? $product->name : 'Unknown Product',
                    'batch_id' => $item['batch_id'],
                    'batch_code' => $batch ? $batch->batch_code : 'Unknown Batch',
                    'quantity' => $item['quantity']
                ];
            }
        }

        $requestData = [
            'id' => $request->id,
            'driver_id' => $request->driver_id,
            'driver_name' => $request->driver ? $request->driver->name : 'N/A',
            'items' => $items,
            'total_quantity' => $request->total_quantity,
            'item_count' => $request->item_count,
            'status' => $request->status,
            'remarks' => $request->remarks,
            'created_at' => $request->created_at ? $request->created_at->format('Y-m-d H:i:s') : 'N/A',
            'approved_by' => $request->approver ? $request->approver->name : null,
            'approved_at' => $request->approved_at ? $request->approved_at->format('Y-m-d H:i:s') : null,
            'rejected_by' => $request->rejector ? $request->rejector->name : null,
            'rejected_at' => $request->rejected_at ? $request->rejected_at->format('Y-m-d H:i:s') : null,
            'rejection_reason' => $request->rejection_reason,
        ];

        return response()->json([
            'success' => true,
            'data' => $requestData
        ]);
    }
}