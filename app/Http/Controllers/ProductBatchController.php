<?php

namespace App\Http\Controllers;

use App\DataTables\ProductBatchDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateProductBatchRequest;
use App\Http\Requests\UpdateProductBatchRequest;
use App\Repositories\ProductBatchRepository;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\ProductBatch;
use App\Models\StockInRequest;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;

class ProductBatchController extends AppBaseController
{
    /** @var ProductBatchRepository $productBatchRepository*/
    private $productBatchRepository;

    public function __construct(ProductBatchRepository $productBatchRepo)
    {
        $this->productBatchRepository = $productBatchRepo;
    }

    /**
     * Display a listing of the Product Batch.
     *
     * @param ProductBatchDataTable $productBatchDataTable
     *
     * @return Response
     */
    public function index(ProductBatchDataTable $productBatchDataTable)
    {
        $warehouses = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'location']);

        return $productBatchDataTable->render('product_batches.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new Product Batch.
     *
     * @return Response
     */
    public function create()
    {
        // Get all active products for dropdown
        $products = Product::where('status', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Get active warehouses for dropdown
        $warehouses = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('product_batches.create', compact('products', 'warehouses'));
    }

    /**
     * Store a newly created Product Batch in storage.
     *
     * @param CreateProductBatchRequest $request
     *
     * @return Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            //'warehouse_id' => 'required|exists:warehouses,id',
            'batch_code' => 'required|string|unique:product_batches,batch_code',
            'expiry_date' => 'required|date',
            'quantity' => 'nullable|integer|min:1',
            'status' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $input = $request->all();
        $input['status'] = 1; // Active

        // Initial stock is not applied on creation — it requires approval.
        // Capture the requested quantity, then create the batch with 0 stock.
        $requestedQuantity = (isset($input['quantity']) && $input['quantity'] > 0)
            ? (int) $input['quantity']
            : 0;
        $input['quantity'] = 0;

        DB::beginTransaction();

        try {
            // Create product batch (with no stock yet)
            $productBatch = $this->productBatchRepository->create($input);

            // If an initial quantity was requested, queue it for approval
            if ($requestedQuantity > 0) {
                StockInRequest::create([
                    'source' => StockInRequest::SOURCE_BATCH_CREATE,
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'requested_quantity' => $requestedQuantity,
                    'quantity' => $requestedQuantity,
                    'remark' => 'Initial stock for batch: ' . $productBatch->batch_code,
                    'status' => StockInRequest::STATUS_PENDING,
                    'requested_by' => Auth::user()->name ?? 'system',
                ]);
            }

            DB::commit();

            Flash::success($requestedQuantity > 0
                ? 'Product Batch created. Initial stock is pending approval.'
                : 'Product Batch created successfully.');

            return redirect(route('productBatches.index'));

        } catch (\Exception $e) {
            DB::rollBack();
            Flash::error('Error creating batch: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified Product Batch.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');

            return redirect(route('productBatches.index'));
        }

        // Get inventory transactions for this batch
        $transactions = InventoryTransaction::where('batch_id', $productBatch->id)
            ->with(['product', 'warehouse'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get warehouse inventory balances for this batch
        $warehouseBalances = WarehouseInventoryBalance::where('batch_id', $productBatch->id)
            ->with('warehouse')
            ->get();

        // Calculate statistics
        $totalStockIn = $transactions->where('type', 1)->sum('quantity');
        $totalStockOut = abs($transactions->where('type', 2)->sum('quantity'));
        $currentStock = $productBatch->quantity;

        return view('product_batches.show', compact(
            'productBatch', 
            'transactions', 
            'warehouseBalances',
            'totalStockIn', 
            'totalStockOut', 
            'currentStock'
        ));
    }

    /**
     * Show the form for editing the specified Product Batch.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');

            return redirect(route('productBatches.index'));
        }

        // Get all active products for dropdown
        $products = Product::where('status', 1)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        // Get active warehouses for dropdown
        $warehouses = Warehouse::where('status', 'active')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();

        return view('product_batches.edit', compact('productBatch', 'products', 'warehouses'));
    }

    /**
     * Update the specified Product Batch in storage.
     *
     * @param int $id
     * @param UpdateProductBatchRequest $request
     *
     * @return Response
     */
    public function update($id, Request $request)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');

            return redirect(route('productBatches.index'));
        }

        $input = $request->all();

        // Don't allow quantity update through edit - use stock in/out functions
        unset($input['quantity']);

        // Validate batch code uniqueness for the same product (excluding current batch)
        $existingBatch = ProductBatch::where('product_id', $input['product_id'])
            ->where('batch_code', $input['batch_code'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingBatch) {
            Flash::error('Batch code already exists for this product.');
            return redirect()->back()->withInput($input);
        }

        $productBatch = $this->productBatchRepository->update($input, $id);

        Flash::success('Product Batch updated successfully.');

        return redirect(route('productBatches.index'));
    }

    /**
     * Remove the specified Product Batch from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');

            return redirect(route('productBatches.index'));
        }

        // Check if batch has any transactions
        $hasTransactions = InventoryTransaction::where('batch_id', $id)->exists();
        
        if ($hasTransactions) {
            Flash::error('Cannot delete batch because it has inventory transactions.');
            return redirect(route('productBatches.index'));
        }

        // Check if batch has stock in any warehouse
        $hasWarehouseStock = WarehouseInventoryBalance::where('batch_id', $id)->exists();
        
        if ($hasWarehouseStock) {
            Flash::error('Cannot delete batch because it has stock in warehouses.');
            return redirect(route('productBatches.index'));
        }

        // Check if batch has stock
        if ($productBatch->quantity > 0) {
            Flash::error('Cannot delete batch with existing stock. Please adjust stock first.');
            return redirect(route('productBatches.index'));
        }

        $this->productBatchRepository->delete($id);

        Flash::success('Product Batch deleted successfully.');

        return redirect(route('productBatches.index'));
    }

    /**
     * Mass delete product batches
     *
     * @param Request $request
     * @return int
     */
    public function massdestroy(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];

        $count = 0;

        foreach ($ids as $id) {
            $productBatch = ProductBatch::find($id);
            
            if ($productBatch) {
                // Check if batch has transactions
                $hasTransactions = InventoryTransaction::where('batch_id', $id)->exists();
                $hasWarehouseStock = WarehouseInventoryBalance::where('batch_id', $id)->exists();
                
                if (!$hasTransactions && !$hasWarehouseStock && $productBatch->quantity == 0) {
                    $productBatch->delete();
                    $count++;
                }
            }
        }

        return $count;
    }

    public function searchByCode(Request $request, $batchCode = null)
    {
        try {
            $code = $batchCode ?: $request->input('batch_code');
            
            if (empty($code)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Batch code is required'
                ], 400);
            }
            
            // Search for batch by batch_code (not by ID)
            $productBatch = ProductBatch::where('batch_code', $code)->first();
        
            if (!$productBatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product Batch not found with code: ' . $code
                ], 404);
            }
            
            // Get active warehouses for dropdown
            $warehouses = Warehouse::where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'location']);

            // Encrypt the ID for safe URL passing
            $encryptedId = Crypt::encrypt($productBatch->id);
            
            return response()->json([
                'success' => true,
                'id' => $productBatch->id,
                'encrypted_id' => $encryptedId,
                'batch_code' => $productBatch->batch_code,
                'product_name' => $productBatch->product->name ?? 'N/A',
                'product_code' => $productBatch->product->unit_code ?? '',
                'expiry_date' => $productBatch->expiry_date instanceof \Carbon\Carbon 
                    ? $productBatch->expiry_date->format('Y-m-d')
                    : $productBatch->expiry_date,
                'quantity' => $productBatch->quantity,
                'status' => $productBatch->status,
                'warehouses' => $warehouses,
                'redirect_url' => route('productBatches.show', $encryptedId)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stockIn(Request $request, $id)//no more use
    {
        try {
            $productBatch = $this->productBatchRepository->find($id);

            if (empty($productBatch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product Batch not found'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1',
                'warehouse_id' => 'required|exists:warehouses,id',
                'remark' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();
            
            try {
                $quantity = $request->quantity;
                $warehouseId = $request->warehouse_id;
                $warehouse = Warehouse::find($warehouseId);

                // Update batch quantity
                $productBatch->increment('quantity', $quantity);

                // Update status to Active if it was Inactive
                if ($productBatch->status == 2) {
                    $productBatch->status = 1; // Active
                    $productBatch->save();
                }

                // Add to warehouse inventory balance
                $warehouseInventory = WarehouseInventoryBalance::firstOrCreate(
                    [
                        'warehouse_id' => $warehouseId,
                        'product_id' => $productBatch->product_id,
                        'batch_id' => $productBatch->id,
                    ],
                    ['quantity' => 0]
                );
                
                $warehouseInventory->increaseQuantity($quantity);

                // Create inventory transaction
                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => $quantity,
                    'type' => 1, // Stock In
                    'remark' => ($request->remark ?? 'Stock in from barcode scan') . ' - Warehouse: ' . $warehouse->name,
                    'date' => now(),
                    'user' => Auth::user()->name ?? 'system',
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $quantity . ' units added to batch in ' . $warehouse->name . ' successfully.',
                    'new_quantity' => $productBatch->quantity,
                    'batch_code' => $productBatch->batch_code
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing stock in: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stockInBulk(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'remark' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.batch_id' => 'required|exists:product_batches,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            $items = collect($request->input('items', []))
                ->groupBy('batch_id')
                ->map(function ($group, $batchId) {
                    return [
                        'batch_id' => (int) $batchId,
                        'quantity' => (int) $group->sum('quantity'),
                    ];
                })
                ->values();

            $batchIds = $items->pluck('batch_id')->all();
            $productBatches = ProductBatch::whereIn('id', $batchIds)->get()->keyBy('id');

            $totalUnits = 0;

            foreach ($items as $item) {
                $productBatch = $productBatches->get($item['batch_id']);

                if (!$productBatch) {
                    throw new \RuntimeException('Product Batch not found');
                }

                $quantity = $item['quantity'];

                // Stock is not applied here — queue an approval request instead.
                StockInRequest::create([
                    'source' => StockInRequest::SOURCE_BULK_SCAN,
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'requested_quantity' => $quantity,
                    'quantity' => $quantity,
                    'remark' => $request->remark ?? 'Bulk stock in from barcode scan',
                    'status' => StockInRequest::STATUS_PENDING,
                    'requested_by' => Auth::user()->name ?? 'system',
                ]);

                $totalUnits += $quantity;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $totalUnits . ' units across ' . $items->count() . ' batch(es) submitted for approval.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error processing bulk stock in: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stockOut(Request $request, $id)
    {
        try {
            $productBatch = $this->productBatchRepository->find($id);

            if (empty($productBatch)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product Batch not found'
                ], 404);
            }

            // Check if batch is expired
            if ($productBatch->isExpired()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot remove stock from expired batch'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer|min:1|max:' . $productBatch->quantity,
                'warehouse_id' => 'required|exists:warehouses,id',
                'remark' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }

            DB::beginTransaction();
            
            try {
                $quantity = $request->quantity;
                $warehouseId = $request->warehouse_id;
                $warehouse = Warehouse::find($warehouseId);

                // Check if warehouse has this batch
                $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $warehouseId)
                    ->where('batch_id', $productBatch->id)
                    ->first();

                if (!$warehouseInventory || $warehouseInventory->quantity < $quantity) {
                    $available = $warehouseInventory ? $warehouseInventory->quantity : 0;
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient quantity in ' . $warehouse->name . '. Available: ' . $available
                    ], 422);
                }

                // Update batch quantity
                $productBatch->decrement('quantity', $quantity);
                
                // Remove from warehouse inventory balance
                $warehouseInventory->decreaseQuantity($quantity);
                
                // Update status based on remaining quantity
                if ($productBatch->quantity <= 0) {
                    $productBatch->status = 2; // Inactive
                    $productBatch->save();
                }

                // Create inventory transaction
                InventoryTransaction::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => -$quantity,
                    'type' => 2, // Stock Out
                    'remark' => ('Stock out from barcode scan') . ' - Warehouse: ' . $warehouse->name,
                    'date' => now(),
                    'user' => Auth::user()->name ?? 'system'
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => $quantity . ' units removed from batch in ' . $warehouse->name . ' successfully.',
                    'new_quantity' => $productBatch->quantity,
                    'batch_code' => $productBatch->batch_code
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error processing stock out: ' . $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Admin-only: directly adjust a batch's aggregate quantity.
     * Writes an InventoryTransaction with TYPE_STOCK_ADJUSTMENT for the audit trail.
     * Warehouse balances are intentionally not modified — drift is the documented trade-off.
     */
    public function adjustStock(Request $request, $id)
    {
        try {
            $id = Crypt::decrypt($id);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid batch identifier'
            ], 400);
        }

        if (!Auth::check() || !Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            return response()->json([
                'success' => false,
                'message' => 'Product Batch not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'new_quantity' => 'required|integer|min:0',
            'remark' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $newQuantity = (int) $request->new_quantity;
        $delta = $newQuantity - (int) $productBatch->quantity;

        if ($delta === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No change — quantity is already ' . $newQuantity,
                'new_quantity' => $newQuantity,
                'no_change' => true,
            ]);
        }

        DB::beginTransaction();

        try {
            $productBatch->quantity = $newQuantity;

            if ($newQuantity == 0) {
                $productBatch->status = 3; // Inactive
            } elseif (in_array($productBatch->status, [2, 3]) && !$productBatch->isExpired()) {
                $productBatch->status = 1; // Active
            }

            $productBatch->save();

            $remarkText = trim((string) $request->remark);
            if ($remarkText === '') {
                $remarkText = 'Inline cell edit';
            }

            InventoryTransaction::create([
                'warehouse_id' => null,
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'quantity' => $delta,
                'type' => InventoryTransaction::TYPE_STOCK_ADJUSTMENT,
                'remark' => 'Admin stock adjustment: ' . $remarkText,
                'date' => now(),
                'user' => Auth::user()->name ?? 'system',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully. New quantity: ' . $newQuantity,
                'new_quantity' => $newQuantity,
                'delta' => $delta,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error adjusting stock: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Check expiry and update status
     */
    public function checkExpiry()
    {
        $expiredBatches = ProductBatch::where('expiry_date', '<', now())
            ->where('status', '!=', 2)
            ->update(['status' => 2]); // Expired

        Flash::success($expiredBatches . ' expired batches updated.');

        return redirect(route('productBatches.index'));
    }

    /**
     * Get batches by product (AJAX)
     */
    public function getBatchesByProduct($productId)
    {
        $batches = ProductBatch::where('product_id', $productId)
            ->where('quantity', '>', 0)
            ->whereDate('expiry_date', '>=', now()->toDateString())
            ->orderBy('expiry_date', 'asc')
            ->get(['id', 'batch_code', 'quantity', 'expiry_date']);

        return response()->json($batches);
    }

    /**
     * Print batch label/QR
     */
    public function printLabel($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');
            return redirect(route('productBatches.index'));
        }
        return $this->downloadBarcode($productBatch->batch_code);
    }
    
    /**
     * Generate barcode preview (AJAX)
     */
    public function generateBarcodePreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'group' => 'required|string|size:1|alpha',
            'product_code' => 'required|string|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);
        $userId = Auth::id();
        
        // Generate batch code
        $batchCode = ProductBatch::generateBatchCode(
            strtoupper($request->group),
            $userId,
            strtoupper($request->product_code)
        );
        
        // Generate barcode image (returns base64 string)
        $barcodeImage = DNS1D::getBarcodePNG($batchCode, 'C128');
        
        // Remove the data:image/png;base64, prefix if present for cleaner response
        $cleanBarcode = $barcodeImage;
        if (strpos($barcodeImage, 'base64,') !== false) {
            $cleanBarcode = substr($barcodeImage, strpos($barcodeImage, 'base64,') + 7);
        }
        
        return response()->json([
            'success' => true,
            'batch_code' => $batchCode,
            'barcode_image' => $cleanBarcode,
            'product_name' => $product->name
        ]);
    }
    
    /**
     * Download barcode image
     */
    public function downloadBarcode($batchCode)
    {
        if (!$batchCode) {
            return response()->json(['error' => 'Batch code is required'], 400);
        }

        $encodeType = ctype_digit($batchCode) ? 'C128C' : 'C128B';
        
        $barcodeBase64 = DNS1D::getBarcodePNG($batchCode, $encodeType, 1, 50, [0,0,0], false);
        $barcode = imagecreatefromstring(base64_decode($barcodeBase64));

        $horizontalPadding = 10;
        $topPadding = 5;
        $bottomPadding = 28;
        
        $width = imagesx($barcode) + ($horizontalPadding * 2);
        $height = imagesy($barcode) + $topPadding + $bottomPadding;

        $canvas = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $black = imagecolorallocate($canvas, 0, 0, 0);

        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $barcode, $horizontalPadding, $topPadding, 0, 0, imagesx($barcode), imagesy($barcode));

        $fontPath = public_path('fonts/DejaVuSans.ttf');
        $fontSize = 10;
        $textBoundingBox = imagettfbbox($fontSize, 0, $fontPath, $batchCode);
        $textWidth = $textBoundingBox[2] - $textBoundingBox[0];
        $textX = max($horizontalPadding, (int) (($width - $textWidth) / 2));
        $textY = $height - 8;

        imagettftext($canvas, $fontSize, 0, $textX, $textY, $black, $fontPath, $batchCode);

        ob_start();
        imagepng($canvas);
        $imageData = ob_get_clean();

        imagedestroy($barcode);
        imagedestroy($canvas);

        return response($imageData)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', "attachment; filename={$batchCode}.png");
    }
}
