<?php

namespace App\Http\Controllers;

use App\DataTables\InventoryBalanceDataTable;
use App\Http\Requests;
use App\Repositories\InventoryBalanceRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\Lorry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\WarehouseInventoryBalance;
use App\Models\Warehouse;

class InventoryBalanceController extends AppBaseController
{
    private $inventoryBalanceRepository;

    public function __construct(InventoryBalanceRepository $inventoryBalanceRepo)
    {
        $this->inventoryBalanceRepository = $inventoryBalanceRepo;
    }

    /**
     * Display a listing of the InventoryBalance.
     */
    public function index(InventoryBalanceDataTable $inventoryBalanceDataTable)
    {
        $availableBatches = ProductBatch::with('product')
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();

        $lorryItems = Lorry::where('status', 1)->orderBy('lorryno')->pluck('lorryno', 'id');
        
        // Add this line to get active warehouses
        $warehouses = \App\Models\Warehouse::where('status', 'active')->get();

        return $inventoryBalanceDataTable->render('inventory_balances.index', compact('lorryItems', 'availableBatches', 'warehouses'));
    }

    /**
     * Show form for stocking in (distributing to lorries)
     */
    public function showStockInForm()
    {
        $lorries = Lorry::where('status', 1)->orderBy('name')->pluck('name', 'id');
        $batches = ProductBatch::with('product')
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get()
            ->mapWithKeys(function ($batch) {
                return [$batch->id => $batch->batch_code . ' - ' . $batch->product->name . ' (' . $batch->quantity . ' units)'];
            });

        return view('inventory_balances.stock_in', compact('lorries', 'batches'));
    }

    /**
     * Stock In - Move multiple batches from one warehouse to one lorry
     */
    public function stockin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'lorry_id' => 'required|exists:lorrys,id',
            'items' => 'required|array|min:1',
            'items.*.product_batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $lorry = Lorry::find($request->lorry_id);
        $warehouseId = $request->warehouse_id;
        $items = collect($request->input('items', []))
            ->groupBy('product_batch_id')
            ->map(function ($group, $batchId) {
                return [
                    'product_batch_id' => (int) $batchId,
                    'quantity' => (int) $group->sum('quantity'),
                ];
            })
            ->values();

        if ($items->isEmpty()) {
            Flash::error('Please scan at least one batch.');
            return redirect()->back()->withInput();
        }

        $batchIds = $items->pluck('product_batch_id')->all();
        $productBatches = ProductBatch::whereIn('id', $batchIds)->get()->keyBy('id');
        $warehouseInventoryMap = WarehouseInventoryBalance::where('warehouse_id', $warehouseId)
            ->whereIn('batch_id', $batchIds)
            ->get()
            ->keyBy('batch_id');

        foreach ($items as $item) {
            $warehouseInventory = $warehouseInventoryMap->get($item['product_batch_id']);
            $productBatch = $productBatches->get($item['product_batch_id']);

            if (!$warehouseInventory || !$productBatch) {
                Flash::error('One or more scanned batches are not available in the selected warehouse.');
                return redirect()->back()->withInput();
            }

            if ($warehouseInventory->quantity < $item['quantity']) {
                Flash::error('Insufficient batch quantity in warehouse for batch ' . $productBatch->batch_code . '. Available: ' . $warehouseInventory->quantity . ', Needed: ' . $item['quantity']);
                return redirect()->back()->withInput();
            }
        }

        DB::beginTransaction();
        
        try {
            $inventoryBalance = InventoryBalance::firstOrCreate(
                ['lorry_id' => $lorry->id],
                ['batches' => []]
            );

            $totalUnits = 0;

            foreach ($items as $item) {
                $productBatch = $productBatches->get($item['product_batch_id']);
                $warehouseInventory = $warehouseInventoryMap->get($item['product_batch_id']);

                // 1. Deduct from warehouse inventory
                $warehouseInventory->decreaseQuantity($item['quantity']);

                // 2. Deduct from product batch master quantity (CRITICAL FIX)
                $productBatch->decreaseQuantity($item['quantity'], "Stock in to lorry {$lorry->lorryno} from warehouse ID: {$warehouseId}");
                
                // 3. Update lorry inventory balance
                $inventoryBalance->updateBatchQuantity(
                    $productBatch->id,
                    $item['quantity'],
                    'add'
                );

                // 4. Create transaction records
                InventoryTransaction::create([
                    'type' => InventoryTransaction::TYPE_STOCK_IN,
                    'lorry_id' => $lorry->id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => $item['quantity'],
                    'date' => now(),
                    'user' => Auth::user()->name,
                    'remark' => 'Received from warehouse'
                ]);

                InventoryTransaction::create([
                    'type' => InventoryTransaction::TYPE_STOCK_OUT,
                    'warehouse_id' => $warehouseId,
                    'lorry_id' => $lorry->id,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => -$item['quantity'],
                    'date' => now(),
                    'user' => Auth::user()->name,
                    'remark' => "Distributed to van #{$lorry->lorryno}."
                ]);

                $totalUnits += $item['quantity'];
            }

            DB::commit();

            Flash::success('Successfully stocked in ' . $totalUnits . ' units across ' . $items->count() . ' batch(es) to lorry ' . $lorry->lorryno . '.');

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error processing stock in: ' . $e->getMessage());
        }

        return redirect(route('inventoryBalances.index'));
    }

    /**
     * Show form for stocking out (removing from lorry)
     */
    public function showStockOutForm()
    {
        $lorries = Lorry::where('status', 1)->orderBy('name')->pluck('name', 'id');
        
        return view('inventory_balances.stock_out', compact('lorries'));
    }

    /**
     * Get batches for a specific lorry (AJAX)
     */
    public function getLorryBatches($lorryId)
    {
        $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();
        
        if (!$inventoryBalance || empty($inventoryBalance->batches)) {
            return response()->json(['batches' => []]);
        }

        $batches = $inventoryBalance->batches_with_details;

        return response()->json(['batches' => $batches]);
    }

    /**
     * Stock Out - Return batch from lorry to warehouse
     */
    public function stockout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lorry_id' => 'required|exists:lorrys,id',
            'batch_id' => 'required|exists:product_batches,id',
            'to_warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $inventoryBalance = InventoryBalance::where('lorry_id', $request->lorry_id)->first();

        if (!$inventoryBalance) {
            Flash::error('No inventory found for this lorry');
            return redirect()->back();
        }

        $currentQuantity = $inventoryBalance->getBatchQuantity($request->batch_id);

        if ($currentQuantity < $request->quantity) {
            Flash::error('Insufficient quantity. Available: ' . $currentQuantity);
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();
        
        try {
            // 1. Get the product batch first
            $batch = ProductBatch::find($request->batch_id);
            
            if (!$batch) {
                throw new \Exception('Product batch not found');
            }
            
            // 2. Add back to product batch master quantity (CRITICAL FIX)
            $batch->increaseQuantity(
                $request->quantity, 
                "Returned from lorry to warehouse ID: {$request->to_warehouse_id}"
            );
            
            // 3. Remove from lorry inventory
            $inventoryBalance->updateBatchQuantity(
                $request->batch_id,
                $request->quantity,
                'subtract'
            );

            // 4. Add to warehouse inventory
            $warehouseInventory = WarehouseInventoryBalance::firstOrCreate(
                [
                    'warehouse_id' => $request->to_warehouse_id,
                    'product_id' => $batch->product_id,
                    'batch_id' => $request->batch_id,
                ],
                ['quantity' => 0]
            );
            
            $warehouseInventory->increaseQuantity($request->quantity);

            // Create inventory transaction for lorry (stock out)
            InventoryTransaction::create([
                'type' => InventoryTransaction::TYPE_RETURN,
                'warehouse_id' => $request->to_warehouse_id,
                'lorry_id' => $request->lorry_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => -$request->quantity,
                'date' => now(),
                'user' => Auth::user()->name,
                'remark' => 'Returned from van to warehouse'
            ]);

            // Create inventory transaction for warehouse (stock in)
            InventoryTransaction::create([
                'type' => InventoryTransaction::TYPE_STOCK_IN,
                'warehouse_id' => $request->to_warehouse_id,
                'lorry_id' => $request->lorry_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => $request->quantity,
                'date' => now(),
                'user' => Auth::user()->name,
                'remark' => 'Received from van'
            ]);

            DB::commit();

            Flash::success($request->quantity . ' units returned from van to warehouse successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error processing stock out: ' . $e->getMessage());
        }

        return redirect(route('inventoryBalances.index'));
    }

    /**
     * Get inventory summary for a lorry
     */
    public function getLorrySummary($lorryId)
    {
        $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();
        
        if (!$inventoryBalance) {
            return response()->json(['total_batches' => 0, 'total_units' => 0, 'batches' => []]);
        }

        $batches = $inventoryBalance->batches_with_details;
        $totalUnits = array_sum($inventoryBalance->batches ?? []);

        return response()->json([
            'total_batches' => count($batches),
            'total_units' => $totalUnits,
            'batches' => $batches
        ]);
    }

    /**
     * Get batches for a specific warehouse (AJAX) - For Stock In Modal
     */
    public function getWarehouseBatches($warehouseId)
    {
        try {
            $warehouse = Warehouse::findOrFail($warehouseId);
            
            $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
                ->where('warehouse_id', $warehouseId)
                ->where('quantity', '>', 0) // Only show batches with stock
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id,
                        'batch_id' => $item->batch_id,
                        'batch_code' => $item->batch ? $item->batch->batch_code : 'Unknown',
                        'product_id' => $item->product_id,
                        'product_name' => $item->product ? $item->product->name : 'Unknown',
                        'product_code' => $item->product ? $item->product->code : 'Unknown',
                        'quantity' => $item->quantity,
                        'expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                        'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
                        'days_to_expiry' => $item->batch ? $item->batch->days_to_expiry : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'warehouse_name' => $warehouse->name,
                'total_batches' => $inventory->count(),
                'total_quantity' => $inventory->sum('quantity'),
                'inventory' => $inventory
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getWarehouseBatches: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading warehouse batches: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warehouses that have a specific batch (for stock out)
     */
    public function getWarehousesWithBatch($batchId)
    {
        try {
            $warehouseBalances = WarehouseInventoryBalance::with('warehouse')
                ->where('batch_id', $batchId)
                ->where('quantity', '>', 0)
                ->get();
            
            $warehouses = $warehouseBalances->map(function($balance) {
                return [
                    'id' => $balance->warehouse_id,
                    'name' => $balance->warehouse->name,
                    'location' => $balance->warehouse->location,
                    'stock' => $balance->quantity
                ];
            });

            return response()->json([
                'success' => true,
                'warehouses' => $warehouses
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer stock between lorries
     */
    // public function transferStock(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'from_lorry_id' => 'required|exists:lorrys,id',
    //         'to_lorry_id' => 'required|exists:lorrys,id|different:from_lorry_id',
    //         'batch_id' => 'required|exists:product_batches,id',
    //         'quantity' => 'required|integer|min:1'
    //     ]);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     DB::beginTransaction();
        
    //     try {
    //         // Remove from source lorry
    //         $fromInventory = InventoryBalance::where('lorry_id', $request->from_lorry_id)->firstOrFail();
    //         $fromInventory->updateBatchQuantity($request->batch_id, $request->quantity, 'subtract');

    //         // Add to destination lorry
    //         $toInventory = InventoryBalance::firstOrCreate(
    //             ['lorry_id' => $request->to_lorry_id],
    //             ['batches' => []]
    //         );
    //         $toInventory->updateBatchQuantity($request->batch_id, $request->quantity, 'add');

    //         // Create transaction records
    //         $batch = ProductBatch::find($request->batch_id);
            
    //         InventoryTransaction::create([
    //             'type' => 3, // Transfer
    //             'lorry_id' => $request->from_lorry_id,
    //             'product_id' => $batch->product_id,
    //             'batch_id' => $request->batch_id,
    //             'quantity' => -$request->quantity,
    //             'date' => now(),
    //             'user' => Auth::user()->name,
    //             'remark' => 'Transferred to lorry ' . $request->to_lorry_id
    //         ]);

    //         InventoryTransaction::create([
    //             'type' => 3, // Transfer
    //             'lorry_id' => $request->to_lorry_id,
    //             'product_id' => $batch->product_id,
    //             'batch_id' => $request->batch_id,
    //             'quantity' => $request->quantity,
    //             'date' => now(),
    //             'user' => Auth::user()->name,
    //             'remark' => 'Received from lorry ' . $request->from_lorry_id
    //         ]);

    //         DB::commit();

    //         Flash::success('Stock transferred successfully.');

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Flash::error('Error transferring stock: ' . $e->getMessage());
    //     }

    //     return redirect(route('inventoryBalances.index'));
    // }
}
