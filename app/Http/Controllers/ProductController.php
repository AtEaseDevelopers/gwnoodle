<?php

namespace App\Http\Controllers;

use App\DataTables\ProductDataTable;
use App\Http\Requests;
use App\Http\Requests\CreateProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepository;
use Flash;
use App\Http\Controllers\AppBaseController;
use Response;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCost; // Add this
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\SpecialPrice;
use App\Models\foc;
use Illuminate\Support\Facades\Session;
use Exception;
use App\Services\EInvoiceService;
use Illuminate\Support\Facades\Validator;

class ProductController extends AppBaseController
{
    /** @var ProductRepository $productRepository*/
    private $productRepository;
    private $eInvoiceService;

    public function __construct(ProductRepository $productRepo, EInvoiceService $eInvoiceService)
    {
        $this->productRepository = $productRepo;
        $this->eInvoiceService = $eInvoiceService;
    }

    /**
     * Display a listing of the Product.
     *
     * @param ProductDataTable $productDataTable
     *
     * @return Response
     */
    public function index(ProductDataTable $productDataTable)
    {
        return $productDataTable->render('products.index');
    }

    /**
     * Show the form for creating a new Product.
     *
     * @return Response
     */
    public function create()
    {
        // Get unique unit codes from existing products
        $unitCodeOptions = $this->getUnitCodeOptions();
        
        // If e-invoice is disabled, return simple view with unit codes
        if (!$this->eInvoiceService->isEnabled()) {
            return view('products.create', compact('unitCodeOptions'));
        }
        
        // If e-invoice is enabled, get classification codes
        $classificationOptions = $this->getClassificationOptions();
        
        return view('products.create', compact('classificationOptions', 'unitCodeOptions'));
    }

    /**
     * Store a newly created Product in storage.
     *
     * @param CreateProductRequest $request
     *
     * @return Response
     */
    public function store(CreateProductRequest $request)
    {
        $input = $request->all();

        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }

        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        // Validate unit code if provided
        if (!empty($input['unit_code'])) {
            $validator = Validator::make($input, [
                'unit_code' => 'string|max:50'
            ]);
            
            if ($validator->fails()) {
                return Redirect::back()->withInput($input)->withErrors($validator->errors());
            }
        }

        // Validate carton fields
        if (isset($input['carton_enabled']) && $input['carton_enabled']) {
            if (empty($input['units_per_carton']) || $input['units_per_carton'] < 1) {
                return Redirect::back()->withInput($input)->withErrors('Units per carton is required and must be at least 1 when carton is enabled');
            }
        }

        // Create product
        $product = $this->productRepository->create($input);

        // NEW: Save initial cost to history if cost is provided
        if (!empty($input['cost'])) {
            ProductCost::create([
                'product_id' => $product->id,
                'cost' => $input['cost'],
                'old_cost' => null,
                'changed_by' => Auth::id(),
                'remarks' => 'Initial cost',
                'created_at' => now()
            ]);
        }

        Flash::success($input['code'].__('products.saved_successfully'));

        return redirect(route('products.index'));
    }

    /**
     * Display the specified Product.
     *
     * @param int $id
     *
     * @return Response
     */
    public function show($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $data = ['product' => $product];
        
        // Add unit code options
        $data['unitCodeOptions'] = $this->getUnitCodeOptions();
        
        // Add classification data if e-invoice is enabled
        if ($this->eInvoiceService->isEnabled()) {
            $data['classificationOptions'] = $this->getClassificationOptions();
            $data['currentClassification'] = $product->classification_code ?? null;
        }

        // NEW: Get cost history for this product
        $data['costHistory'] = ProductCost::where('product_id', $product->id)
            ->with('changer')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('products.show', $data);
    }

    /**
     * Show the form for editing the specified Product.
     *
     * @param int $id
     *
     * @return Response
     */
    public function edit($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $data = ['product' => $product];
        
        // Get unique unit codes from existing products
        $data['unitCodeOptions'] = $this->getUnitCodeOptions();
        
        // Add classification data if e-invoice is enabled
        if ($this->eInvoiceService->isEnabled()) {
            $data['classificationOptions'] = $this->getClassificationOptions();
            $data['currentClassification'] = $product->classification_code ?? null;
        }

        return view('products.edit', $data);
    }

    /**
     * Update the specified Product in storage.
     *
     * @param int $id
     * @param UpdateProductRequest $request
     *
     * @return Response
     */
    public function update($id, UpdateProductRequest $request)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $input = $request->all();

        if(str_contains($input['name'],'"')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain double quote');
        }

        if(str_contains($input['name'],'\'')){
            return Redirect::back()->withInput($input)->withErrors('The name cannot contain single quote');
        }

        // Validate unit code if provided
        if (!empty($input['unit_code'])) {
            $validator = Validator::make($input, [
                'unit_code' => 'string|max:50'
            ]);
            
            if ($validator->fails()) {
                return Redirect::back()->withInput($input)->withErrors($validator->errors());
            }
        }

        // Validate carton fields
        if (isset($input['carton_enabled']) && $input['carton_enabled']) {
            if (empty($input['units_per_carton']) || $input['units_per_carton'] < 1) {
                return Redirect::back()->withInput($input)->withErrors('Units per carton is required and must be at least 1 when carton is enabled');
            }
        }

        // NEW: Check if cost changed before update
        $oldCost = $product->cost;
        $newCost = $input['cost'] ?? null;

        // Update product
        $product = $this->productRepository->update($input, $id);

        // NEW: If cost changed, save to history
        if ($oldCost != $newCost && !is_null($newCost)) {
            ProductCost::create([
                'product_id' => $product->id,
                'cost' => $newCost,
                'old_cost' => $oldCost,
                'changed_by' => Auth::id(),
                'remarks' => $request->cost_remarks ?? 'Cost updated',
                'created_at' => now()
            ]);

            // Optional: Flash message about cost change
            Flash::info('Cost changed from ' . number_format($oldCost, 2) . ' to ' . number_format($newCost, 2));
        }

        Flash::success($product->code.__('products.updated_successfully'));

        return redirect(route('products.index'));
    }

    /**
     * Remove the specified Product from storage.
     *
     * @param int $id
     *
     * @return Response
     */
    public function destroy($id)
    {
        $id = Crypt::decrypt($id);
        $product = $this->productRepository->find($id);

        if (empty($product)) {
            Flash::error(__('products.product_not_found'));

            return redirect(route('products.index'));
        }

        $SpecialPrice = SpecialPrice::where('product_id',$id)->get()->toArray();
        if(count($SpecialPrice)>0){
            Flash::error('Unable to delete '.$product->name.', '.$product->name.' is being used in Special Price');

            return redirect(route('products.index'));
        }

        $foc = foc::where('product_id',$id)->get()->toArray();
        if(count($foc)>0){
            Flash::error('Unable to delete '.$product->name.', '.$product->name.' is being used in Foc');

            return redirect(route('products.index'));
        }

        // NEW: Also delete cost history (optional - you might want to keep for audit)
        // ProductCost::where('product_id', $id)->delete();

        $this->productRepository->delete($id);

        Flash::success($product->code.__('products.deleted_successfully'));

        return redirect(route('products.index'));
    }

    /**
     * Mass delete products
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

            $Invoice = InvoiceDetail::where('product_id',$id)->get()->toArray();
            if(count($Invoice)>0){
                continue;
            }

            $SpecialPrice = SpecialPrice::where('product_id',$id)->get()->toArray();
            if(count($SpecialPrice)>0){
                continue;
            }

            $foc = foc::where('product_id',$id)->get()->toArray();
            if(count($foc)>0){
                continue;
            }

            // NEW: Also delete cost history (optional)
            // ProductCost::where('product_id', $id)->delete();

            $count = $count + Product::destroy($id);
        }

        return $count;
    }

    /**
     * Mass update product status
     *
     * @param Request $request
     * @return int
     */
    public function massupdatestatus(Request $request)
    {
        $data = $request->all();
        $ids = $data['ids'];
        $status = $data['status'];

        $count = Product::whereIn('id',$ids)->update(['status'=>$status]);

        return $count;
    }
    
    /**
     * Sync products with Xero
     *
     * @param Request $req
     * @return Response
     */
    public function syncXero(Request $req)
    {
        try {
            $redirect_uri = config('app.url') . '/products/sync-xero';
            $xero = new XeroController($redirect_uri);

            if ($req->has('ids')) {
                $ids = explode(',', $req->ids);
                Session::put('ids_to_sync_xero', $ids);
            }
            // Get Xero's access token
            if ($req->has('code')) {
                $res = $xero->getToken($req->code);
                if (!$res->ok()) {
                    throw new Exception('Failed to get xero access token.');
                }
            }
            // Xero auth
            $res = $xero->auth();
            if ($res !== true) {
                return $res;
            }
            // Sync products
            $ids = Session::get('ids_to_sync_xero');
            $products = Product::whereIn('id',$ids)->get();
            
            for ($i = 0; $i < count($products) ;$i++) {
                $res = $xero->createItem($products[$i]->code, $products[$i]->name, $products[$i]->price);

                if (!$res->ok()) {  
                    throw new Exception('Failed to sync product.');
                }
            }
            
            Flash::success('Products synced to Xero.');
            return redirect(route('products.index'));
        } catch (\Throwable $th) {
            report($th);
            
            Flash::error('Something went wrong. Please contact administator.');
            return redirect(route('products.index'));
        }
    }

    /**
     * Store a new unit code via AJAX
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function storeUnitCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unit_code' => 'required|string|max:50|unique:products,unit_code',
            'description' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // We don't actually save the unit code here
            // It will be saved with the product when the form is submitted
            // We just validate that it's available
            
            // Optionally, you could store unit codes in a separate table
            // UnitCode::create([
            //     'unit_code' => $request->unit_code,
            //     'description' => $request->description
            // ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Unit code is available',
                'unit_code' => $request->unit_code,
                'description' => $request->description
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to validate unit code: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all unique unit codes via AJAX
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUnitCodes()
    {
        try {
            // Get all unique unit codes from products
            $unitCodes = Product::whereNotNull('unit_code')
                ->select('unit_code')
                ->distinct()
                ->orderBy('unit_code')
                ->get()
                ->map(function($item) {
                    return [
                        'unit_code' => $item->unit_code,
                        'description' => $item->unit_code // You might want to enhance this
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $unitCodes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch unit codes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get classification options for e-invoice
     *
     * @return array
     */
    protected function getClassificationOptions()
    {
        return \App\Models\ClassificationCode::orderBy('code')->get()->mapWithKeys(function ($code) {
            return [$code->code => $code->code . ' - ' . $code->description];
        })->toArray();
    }

    /**
     * Get unit code options for select dropdown
     *
     * @return array
     */
    protected function getUnitCodeOptions()
    {
        return Product::whereNotNull('unit_code')
            ->distinct()
            ->pluck('unit_code', 'unit_code')
            ->mapWithKeys(function ($unitCode) {
                return [$unitCode => $unitCode];
            })
            ->toArray();
    }

    /**
     * Validate unit code uniqueness
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateUnitCode(Request $request)
    {
        $unitCode = $request->get('unit_code');
        $excludeId = $request->get('exclude_id');
        
        $query = Product::where('unit_code', $unitCode);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        $exists = $query->exists();
        
        return response()->json([
            'unique' => !$exists,
            'message' => $exists ? 'Unit code already exists' : 'Unit code is available'
        ]);
    }

    /**
     * NEW: Get cost history for a specific product (AJAX)
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCostHistory($id)
    {
        try {
            $id = Crypt::decrypt($id);
            $history = ProductCost::where('product_id', $id)
                ->with('changer')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($item) {
                    return [
                        'date' => $item->created_at->format('d-m-Y H:i:s'),
                        'old_cost' => $item->old_cost ? number_format($item->old_cost, 2) : '-',
                        'new_cost' => number_format($item->cost, 2),
                        'changed_by' => $item->changer->name ?? 'System',
                        'remarks' => $item->remarks ?? '-'
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch cost history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * NEW: Add cost remark when updating (optional)
     * You can add this field to your edit form
     */
    protected function validateCostRemarks($input)
    {
        // Optional validation for cost remarks
        if (isset($input['cost_remarks'])) {
            $validator = Validator::make($input, [
                'cost_remarks' => 'nullable|string|max:255'
            ]);
            
            if ($validator->fails()) {
                return Redirect::back()->withErrors($validator->errors());
            }
        }
    }
}