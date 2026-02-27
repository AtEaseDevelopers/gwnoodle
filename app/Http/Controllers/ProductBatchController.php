<?php

namespace App\Http\Controllers;

use App\DataTables\ProductBatchDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateProductBatchRequest;
use App\Http\Requests\UpdateProductBatchRequest;
use App\Repositories\ProductBatchRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use App\Models\ProductBatch;
use App\Models\Product;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Exception;

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
        return $productBatchDataTable->render('product_batches.index');
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

        return view('product_batches.create', compact('products'));
    }

    /**
     * Store a newly created Product Batch in storage.
     *
     * @param CreateProductBatchRequest $request
     *
     * @return Response
     */
    public function store(CreateProductBatchRequest $request)
    {
        $input = $request->all();

        // Validate batch code uniqueness for the same product
        $existingBatch = ProductBatch::where('product_id', $input['product_id'])
            ->where('batch_code', $input['batch_code'])
            ->first();

        if ($existingBatch) {
            Flash::error('Batch code already exists for this product.');
            return redirect()->back()->withInput($input);
        }

        // Set initial quantity equal to quantity
        $input['initial_quantity'] = $input['quantity'];
        $input['status'] = 1; // Active

        DB::beginTransaction();
        
        try {
            // Create product batch
            $productBatch = $this->productBatchRepository->create($input);

            // Create inventory transaction for stock in
            InventoryTransaction::create([
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'quantity' => $input['quantity'],
                'type' => 1, // Stock In
                'transaction_type' => 'stock_in',
                'remark' => 'Initial stock for batch: ' . $productBatch->batch_code,
                'date' => now(),
                'user' => Auth::user()->name ?? 'system'
            ]);

            DB::commit();

            Flash::success('Product Batch saved successfully.');

            return redirect(route('productBatches.index'));
        } catch (\Exception $e) {
            DB::rollBack();
            
            Flash::error('Error creating batch: ' . $e->getMessage());
            return redirect()->back()->withInput($input);
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
            ->with('product')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate statistics
        $totalStockIn = $transactions->where('type', 1)->sum('quantity');
        $totalStockOut = abs($transactions->where('type', 2)->sum('quantity'));
        $currentStock = $productBatch->quantity;

        return view('product_batches.show', compact(
            'productBatch', 
            'transactions', 
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

        return view('product_batches.edit', compact('productBatch', 'products'));
    }

    /**
     * Update the specified Product Batch in storage.
     *
     * @param int $id
     * @param UpdateProductBatchRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateProductBatchRequest $request)
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
        unset($input['initial_quantity']);

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
                
                if (!$hasTransactions && $productBatch->quantity == 0) {
                    $productBatch->delete();
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Show form for stock in
     */
    public function showStockInForm($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');
            return redirect(route('productBatches.index'));
        }

        return view('product_batches.stock_in', compact('productBatch'));
    }

    /**
     * Process stock in
     */
    public function stockIn(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');
            return redirect(route('productBatches.index'));
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            $quantity = $request->quantity;

            // Update batch quantity
            $productBatch->increment('quantity', $quantity);
            $productBatch->initial_quantity += $quantity; // Add to initial quantity
            $productBatch->save();

            // Create inventory transaction
            InventoryTransaction::create([
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'quantity' => $quantity,
                'type' => 1, // Stock In
                'transaction_type' => 'stock_in',
                'remark' => $request->remark ?? 'Additional stock in',
                'date' => now(),
                'user' => Auth::user()->name ?? 'system'
            ]);

            DB::commit();

            Flash::success($quantity . ' units added to batch successfully.');

            return redirect(route('productBatches.show', ['id' => Crypt::encrypt($productBatch->id)]));

        } catch (\Exception $e) {
            DB::rollBack();
            
            Flash::error('Error processing stock in: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Show form for stock out
     */
    public function showStockOutForm($id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');
            return redirect(route('productBatches.index'));
        }

        return view('product_batches.stock_out', compact('productBatch'));
    }

    /**
     * Process stock out
     */
    public function stockOut(Request $request, $id)
    {
        $id = Crypt::decrypt($id);
        $productBatch = $this->productBatchRepository->find($id);

        if (empty($productBatch)) {
            Flash::error('Product Batch not found');
            return redirect(route('productBatches.index'));
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1|max:' . $productBatch->quantity,
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        
        try {
            $quantity = $request->quantity;

            // Update batch quantity
            $productBatch->decrement('quantity', $quantity);
            
            // Update status based on remaining quantity
            if ($productBatch->quantity <= 0) {
                $productBatch->status = 3; // Depleted
            }
            $productBatch->save();

            // Create inventory transaction
            InventoryTransaction::create([
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'quantity' => -$quantity, // Negative for stock out
                'type' => 2, // Stock Out
                'transaction_type' => 'stock_out',
                'remark' => $request->remark ?? 'Stock out',
                'date' => now(),
                'user' => Auth::user()->name ?? 'system'
            ]);

            DB::commit();

            Flash::success($quantity . ' units removed from batch successfully.');

            return redirect(route('productBatches.show', ['id' => Crypt::encrypt($productBatch->id)]));

        } catch (\Exception $e) {
            DB::rollBack();
            
            Flash::error('Error processing stock out: ' . $e->getMessage());
            return redirect()->back()->withInput();
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
            ->where('expiry_date', '>', now())
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

        return view('product_batches.label', compact('productBatch'));
    }
}