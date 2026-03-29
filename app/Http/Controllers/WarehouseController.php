<?php

namespace App\Http\Controllers;

use App\DataTables\WarehouseDataTable;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryBalance;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Flash;

class WarehouseController extends Controller
{
    /**
     * Display a listing of the warehouses.
     */
    public function index(WarehouseDataTable $warehouseDataTable)
    {
        $products = Product::orderBy('name')->pluck('name', 'id');
        $warehouses = Warehouse::where('status', 'active')->get();
        $batches = ProductBatch::with('product')
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->mapWithKeys(function ($batch) {
                return [$batch->id => $batch->batch_code . ' - ' . $batch->product->name . ' (' . $batch->quantity . ' units)'];
            });

        return $warehouseDataTable->render('warehouses.index', compact('products', 'batches','warehouses'));
    }

    /**
     * Show the form for creating a new warehouse.
     */
    public function create()
    {
        return view('warehouses.create');
    }

    /**
     * Store a newly created warehouse in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        
        try {
            // Create warehouse
            $warehouse = Warehouse::create([
                'name' => $request->name,
                'location' => $request->location,
                'status' => 'active', // Default status
            ]);

            DB::commit();

            Flash::success('Warehouse created successfully.');
            return redirect(route('warehouses.index'));

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error creating warehouse: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified warehouse with its inventory.
     */
    public function show($id)
    {
        $warehouse = Warehouse::with(['inventoryBalances' => function($query) {
            $query->with(['product', 'batch']);
        }])->findOrFail($id);

        // Calculate totals
        $totalProducts = $warehouse->inventoryBalances->groupBy('product_id')->count();
        $totalBatches = $warehouse->inventoryBalances->count();
        $totalQuantity = $warehouse->inventoryBalances->sum('quantity');

        // Group by product for summary
        $productsSummary = $warehouse->inventoryBalances
            ->groupBy('product_id')
            ->map(function($items, $productId) {
                $product = $items->first()->product;
                return [
                    'product_id' => $productId,
                    'product_name' => $product ? $product->name : 'Unknown',
                    'total_quantity' => $items->sum('quantity'),
                    'batch_count' => $items->count(),
                    'batches' => $items
                ];
            });

        return view('warehouses.show', compact('warehouse', 'totalProducts', 'totalBatches', 'totalQuantity', 'productsSummary'));
    }

    /**
     * Show the form for editing the specified warehouse.
     */
    public function edit($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        return view('warehouses.edit', compact('warehouse'));
    }

    /**
     * Update the specified warehouse in storage.
     */
    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $warehouse->update([
                'name' => $request->name,
                'location' => $request->location,
                'status' => $request->status,
            ]);

            Flash::success('Warehouse updated successfully.');
            return redirect(route('warehouses.index'));

        } catch (\Exception $e) {
            Flash::error('Error updating warehouse: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified warehouse from storage.
     */
    public function destroy($id)
    {
        $warehouse = Warehouse::findOrFail($id);

        // Check if warehouse has inventory
        if ($warehouse->inventoryBalances()->count() > 0) {
            Flash::error('Cannot delete warehouse with existing inventory. Please remove inventory first.');
            return redirect(route('warehouses.index'));
        }

        try {
            $warehouse->delete();
            Flash::success('Warehouse deleted successfully.');
        } catch (\Exception $e) {
            Flash::error('Error deleting warehouse: ' . $e->getMessage());
        }

        return redirect(route('warehouses.index'));
    }

    /**
     * Show form for adding inventory to warehouse
     */
    public function showAddInventoryForm($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $products = Product::orderBy('name')->pluck('name', 'id');
        $batches = ProductBatch::with('product')
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->mapWithKeys(function ($batch) {
                return [$batch->id => $batch->batch_code . ' - ' . $batch->product->name . ' (' . $batch->quantity . ' units, Exp: ' . $batch->formatted_expiry_date . ')'];
            });

        return view('warehouses.add_inventory', compact('warehouse', 'products', 'batches'));
    }

    /**
     * Show form for removing inventory from warehouse
     */
    public function showRemoveInventoryForm($warehouseId, $inventoryId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
            ->where('warehouse_id', $warehouseId)
            ->findOrFail($inventoryId);

        return view('warehouses.remove_inventory', compact('warehouse', 'inventory'));
    }

    /**
     * Get inventory summary for a warehouse (AJAX)
     */
    public function getWarehouseSummary($warehouseId)
    {
        $warehouse = Warehouse::with(['inventoryBalances' => function($query) {
            $query->with(['product', 'batch']);
        }])->findOrFail($warehouseId);

        $inventory = $warehouse->inventoryBalances->map(function($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product ? $item->product->name : 'Unknown',
                'batch_id' => $item->batch_id,
                'batch_code' => $item->batch ? $item->batch->batch_code : 'Unknown',
                'quantity' => $item->quantity,
                'expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
            ];
        });

        return response()->json([
            'warehouse_name' => $warehouse->name,
            'total_products' => $warehouse->inventoryBalances->groupBy('product_id')->count(),
            'total_batches' => $warehouse->inventoryBalances->count(),
            'total_quantity' => $warehouse->inventoryBalances->sum('quantity'),
            'inventory' => $inventory
        ]);
    }

    /**
     * Get products for dropdown (AJAX)
     */
    public function getProducts()
    {
        $products = Product::orderBy('name')->get(['id', 'name', 'code']);
        return response()->json($products);
    }

    /**
     * Get batches for a specific product (AJAX)
     */
    public function getProductBatches($productId)
    {
        $batches = ProductBatch::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->formatted_expiry_date,
                    'is_expiring_soon' => $batch->isExpiringSoon()
                ];
            });

        return response()->json($batches);
    }
    public function getWarehouseBatches($warehouseId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        
        $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'batch_id' => $item->batch_id,
                    'batch_code' => $item->batch ? $item->batch->batch_code : 'Unknown',
                    'quantity' => $item->quantity,
                    'expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                    'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
                ];
            });

            return response()->json([
            'warehouse_name' => $warehouse->name,
            'total_batches' => $inventory->count(),
            'total_quantity' => $inventory->sum('quantity'),
            'inventory' => $inventory
        ]);
    }

    /**
     * Get product batches from a specific warehouse (AJAX)
     */
    public function getWarehouseProductBatches(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid parameters',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $warehouseId = $request->warehouse_id;
            $productId = $request->product_id;
            
            $inventory = WarehouseInventoryBalance::with(['batch'])
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->where('quantity', '>', 0)
                ->get()
                ->map(function($item) {
                    return [
                        'batch_id' => $item->batch_id,
                        'batch_code' => $item->batch ? $item->batch->batch_code : 'Unknown',
                        'quantity' => $item->quantity,
                        'expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                        'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
                    ];
                });

            return response()->json([
                'success' => true,
                'batches' => $inventory
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getWarehouseProductBatches: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading batches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouse inventory (AJAX) - corresponds to warehouses.get-inventory
     */
    public function getWarehouseInventory($warehouseId)
    {
        $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
            ->where('warehouse_id', $warehouseId)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product ? $item->product->name : 'Unknown',
                    'product_code' => $item->product ? $item->product->code : 'Unknown',
                    'batch_id' => $item->batch_id,
                    'batch_code' => $item->batch ? $item->batch->batch_code : 'Unknown',
                    'quantity' => $item->quantity,
                    'expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                    'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
                ];
            });

        return response()->json([
            'success' => true,
            'inventory' => $inventory
        ]);
    }

    /**
     * Edit inventory item - corresponds to warehouses.edit-inventory
     */
    public function editInventory($warehouseId, $inventoryId)
    {
        $warehouse = Warehouse::findOrFail($warehouseId);
        $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
            ->where('warehouse_id', $warehouseId)
            ->findOrFail($inventoryId);

        return view('warehouses.edit_inventory', compact('warehouse', 'inventory'));
    }

    /**
     * Update inventory item - corresponds to warehouses.update-inventory
     */
    public function updateInventory(Request $request, $warehouseId, $inventoryId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:0',
            'adjustment_reason' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $inventory = WarehouseInventoryBalance::where('warehouse_id', $warehouseId)
            ->findOrFail($inventoryId);

        $oldQuantity = $inventory->quantity;
        $newQuantity = $request->quantity;
        $difference = $newQuantity - $oldQuantity;

        DB::beginTransaction();
        
        try {
            $inventory->quantity = $newQuantity;
            $inventory->save();

            // Only create transaction if there's a change
            if ($difference != 0) {
                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $inventory->product_id,
                    'batch_id' => $inventory->batch_id,
                    'quantity' => $difference,
                    'type' => 4, // You may want to add a new type for adjustments (e.g., TYPE_ADJUSTMENT = 4)
                    'remark' => 'Manual adjustment: ' . $oldQuantity . ' → ' . $newQuantity . ($request->adjustment_reason ? ' - ' . $request->adjustment_reason : ''),
                    'date' => now(),
                    'user' => Auth::user()->name ?? 'System',
                ]);
            }

            DB::commit();

            Flash::success('Inventory quantity updated from ' . $oldQuantity . ' to ' . $newQuantity . ' successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error updating inventory: ' . $e->getMessage());
        }

        return redirect(route('warehouses.show', $warehouseId));
    }

    /**
     * Show transfer form - corresponds to warehouses.transfer-form
     */
    public function showTransferForm()
    {
        $warehouses = Warehouse::where('status', 'active')->orderBy('name')->get();
        $products = Product::orderBy('name')->get();
        
        return view('warehouses.transfer_form', compact('warehouses', 'products'));
    }

    /**
     * Transfer stock between warehouses - corresponds to warehouses.transfer
     */
    public function transferStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'remarks' => 'nullable|string|max:500',
            'items' => 'nullable|array|min:1',
            'items.*.batch_id' => 'required_with:items|exists:product_batches,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'batch_id' => 'required_without:items|exists:product_batches,id',
            'quantity' => 'required_without:items|integer|min:1',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $fromWarehouse = Warehouse::findOrFail($request->from_warehouse_id);
        $toWarehouse = Warehouse::findOrFail($request->to_warehouse_id);
        $items = collect($request->input('items', []));

        if ($items->isEmpty()) {
            $items = collect([[
                'batch_id' => (int) $request->batch_id,
                'quantity' => (int) $request->quantity,
            ]]);
        } else {
            $items = $items
                ->groupBy('batch_id')
                ->map(function ($group, $batchId) {
                    return [
                        'batch_id' => (int) $batchId,
                        'quantity' => (int) $group->sum('quantity'),
                    ];
                })
                ->values();
        }

        $batchIds = $items->pluck('batch_id')->all();
        $batches = ProductBatch::with('product')->whereIn('id', $batchIds)->get()->keyBy('id');
        $sourceInventories = WarehouseInventoryBalance::where('warehouse_id', $request->from_warehouse_id)
            ->whereIn('batch_id', $batchIds)
            ->get()
            ->keyBy('batch_id');

        foreach ($items as $item) {
            $sourceInventory = $sourceInventories->get($item['batch_id']);
            $batch = $batches->get($item['batch_id']);

            if (!$batch || !$sourceInventory || $sourceInventory->quantity < $item['quantity']) {
                $available = $sourceInventory ? $sourceInventory->quantity : 0;
                $batchCode = $batch ? $batch->batch_code : ('#' . $item['batch_id']);
                Flash::error('Insufficient quantity for batch ' . $batchCode . '. Available: ' . $available . ', Requested: ' . $item['quantity']);
                return redirect()->back()->withInput();
            }
        }

        DB::beginTransaction();
        
        try {
            $totalUnits = 0;

            foreach ($items as $item) {
                $batch = $batches->get($item['batch_id']);
                $sourceInventory = $sourceInventories->get($item['batch_id']);

                $sourceInventory->decreaseQuantity($item['quantity']);

                $destInventory = WarehouseInventoryBalance::firstOrCreate(
                    [
                        'warehouse_id' => $request->to_warehouse_id,
                        'product_id' => $batch->product_id,
                        'batch_id' => $batch->id,
                    ],
                    ['quantity' => 0]
                );
                $destInventory->increaseQuantity($item['quantity']);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $request->from_warehouse_id,
                    'product_id' => $batch->product_id,
                    'batch_id' => $batch->id,
                    'quantity' => -$item['quantity'],
                    'type' => InventoryTransaction::TYPE_TRANSFER,
                    'remark' => 'Stock transferred to warehouse: -[' . $toWarehouse->name. ']',
                    'date' => now(),
                    'user' => Auth::user()->name ?? 'System',
                ]);

                \App\Models\InventoryTransaction::create([
                    'warehouse_id' => $request->to_warehouse_id,
                    'product_id' => $batch->product_id,
                    'batch_id' => $batch->id,
                    'quantity' => $item['quantity'],
                    'type' => InventoryTransaction::TYPE_TRANSFER,
                    'remark' => 'Stock received from warehouse: -[' . $fromWarehouse->name.']  ',
                    'date' => now(),
                    'user' => Auth::user()->name ?? 'System',
                ]);

                $totalUnits += $item['quantity'];
            }

            DB::commit();

            Flash::success($totalUnits . ' units across ' . $items->count() . ' batch(es) transferred from ' . $fromWarehouse->name . ' to ' . $toWarehouse->name . ' successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error transferring stock: ' . $e->getMessage());
        }

        return redirect(route('warehouses.index'));
    }

    /**
     * Get batches for dropdown (AJAX) - corresponds to warehouses.get-batches
     */
    public function getBatches()
    {
        $batches = ProductBatch::with('product')
            ->where('quantity', '>', 0)
            ->orderBy('expiry_date')
            ->get()
            ->map(function($batch) {
                return [
                    'id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'product_id' => $batch->product_id,
                    'product_name' => $batch->product->name,
                    'quantity' => $batch->quantity,
                    'expiry_date' => $batch->formatted_expiry_date,
                    'is_expiring_soon' => $batch->isExpiringSoon()
                ];
            });

        return response()->json([
            'success' => true,
            'batches' => $batches
        ]);
    }

    /**
     * Get warehouses list for dropdown (AJAX) - corresponds to warehouses.get-list
     */
    public function getWarehousesList()
    {
        $warehouses = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'location']);

        return response()->json([
            'success' => true,
            'warehouses' => $warehouses
        ]);
    }
}
