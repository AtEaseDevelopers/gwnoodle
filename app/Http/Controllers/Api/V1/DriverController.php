<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Datetime;
use App\Models\User;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Kelindan;
use App\Models\Lorry;
use App\Models\Task;
use App\Models\TaskTransfer;
use App\Models\Assign;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockInRequest;
use App\Models\ProductCost;
use App\Models\SpecialPrice;
use App\Models\Customer;
use App\Models\InvoicePayment;
use App\Models\InvoiceDetail;
use App\Models\Code;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransfer;
use App\Models\InventoryCount;
use App\Models\InventoryReturn;
use App\Models\foc;
use App\Models\DriverLocation;
use App\Models\Language;
use App\Models\MobileTranslationVersion;
use App\Models\MobileTranslation;
use App\Models\Warehouse;
use App\Models\WarehouseInventoryBalance;
use Milon\Barcode\Facades\DNS1DFacade as DNS1D;

use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    protected $message_separator = "|";
    //Auth
    public function login(Request $request){
        // return "000002" <=> "000002";
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'employeeid' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('employeeid', $data['employeeid'])->where('password', $data['password'])->first();
            if(!empty($driver)){
                $session = $driver->session;
                $driver->session = session_create_id();
                $driver->save();

                // trip_id is the source of truth for "driver currently has
                // an active trip" (set on start, cleared on end) - fetch the
                // actual trip record only when one is actually active.
                $trip = !empty($driver->trip_id)
                    ? Trip::where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first()
                    : null;
                $status = !empty($trip);

                $colorcode = Code::where('code','color_code_'.date("D"))->first()['value'] ?? '';

                if($status){
                    if($session == null){
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => true,
                                        'trip' => $trip
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }else{
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.previous_session_override',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => true,
                                        'trip' => $trip
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }
                }else{
                    if($session == null){
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => false
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }else{
                        return response()->json([
                                'result' => true,
                                'message' => __LINE__.$this->message_separator.'api.message.previous_session_override',
                                'data' => [
                                    'driver' => $driver,
                                    'trip' => [
                                        'status' => false
                                    ],
                                'colorcode' => $colorcode
                            ]
                        ], 200);
                    }
                }

            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_credential',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function logout(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'session' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('session', $data['session'])->first();
            if(!empty($driver)){
                $driver->session = NULL;
                $driver->save();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.logout_successfully',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function session(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'session' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $driver = Driver::where('session', $data['session'])->first();
            $colorcode = Code::where('code','color_code_'.date("D"))->first()['value'] ?? '';
            if(!empty($driver)){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.session_found',

                    'data' => $driver,
                    'colorcode' => $colorcode
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function location(Request $request){
        $data = $request->all();
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'latitude' => 'required|numeric',
                'longitude' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $DriverLocation = new DriverLocation();
            $DriverLocation->date = $data['date'];
            $DriverLocation->latitude = $data['latitude'];
            $DriverLocation->longitude = $data['longitude'];
            $DriverLocation->driver_id = $trip->driver_id;
            $DriverLocation->kelindan_id = $trip->kelindan_id;
            $DriverLocation->lorry_id = $trip->lorry_id;
            $DriverLocation->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.driver_location_had_been_updated_successfully',
                'data' => $DriverLocation
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //Trip
    public function checktrip(Request $request){
        try{
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => [
                        'status' => false
                    ]
                ], 200);
            }

            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                'data' => [
                    'status' => true,
                    'trip' => $trip
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function starttrip(Request $request)
    {

        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        
        if($driver->trip_id != NULL ){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver have to end trip before start new trip.',
                'data' => null
            ], 200);
        }
        $lorry = Lorry::where('id', $request->lorry_id)->first();

        if($lorry->status == 0 ){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'The selected van is in use by other driver.',
                'data' => null
            ], 200);
        }

        try {

            $trip = Trip::create([
                'lorry_id'=>$request->lorry_id,
                'date'=> now(),
                'uuid' => Trip::generateUniqueReference(),
                'driver_id' => $driver->id,
                'type' => Trip::START_TRIP,
            ]);
             //generate task
                $assigns = Assign::where('driver_id', $driver->id)->active()->whereHas('customer', function ($q) { $q->where('status', 1); })->orderby('sequence','asc')->pluck('customer_id')->unique()->values()->all();
                $count = 1;
                foreach($assigns as $assign){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $assign;
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->trip_id = $trip->id;
                    $task->save();
                    $count = $count + 1;
                }

            //store lorry status to 0 when in use
            $lorry->status = 0;
            $lorry->driver_id = $driver->id;
            $lorry->save();

            $driver->trip_id = $trip->uuid; 
            $driver->save();
                
            return response()->json([
                'success' => true,
                'message' => 'Driver Start Trip successfully.',
                'data' => $trip
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create request: ' . $e->getMessage()
            ], 200);
        }
    }

    public function tripEnd(Request $request)
    {
        // The trip now actually ends when a manager/admin approves the
        // driver's stock count (see InventoryCount::endDriverTrip(),
        // called from DriverController::approveStockCount() and
        // InventoryCountController::approve()). This endpoint is kept
        // around purely so the driver app's existing "end trip" call still
        // gets a success response, without doing anything itself anymore.
        return response()->json([
            'success' => true,
            'message' => 'Driver End Trip successfully.',
            'data' => ''
        ]);
    }



    //Lorry
    public function getlorry(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //process
            // $lorry = Lorry::where('status',1)->select('id','lorryno')->get()->toarray();
            $lorry = Lorry::where('status',1)->select('id','lorryno')->get();

            if(count($lorry) != 0){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.lorry_found',
                    'data' => $lorry
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.lorry_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function switchLorry(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'lorry_id' => 'required|numeric'
            ]);

            $trip = Trip::where('uuid', $driver->trip_id)->first();
            if(!$trip){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Trip not found.',
                    'data' => null
                ], 401);
            } 

            $currentLorryId = $trip->lorry_id;
            $newLorryId = $request->lorry_id;
                        
            //update trip lorry
            $trip->lorry_id = $newLorryId;
            $trip->save();

            //update lorry status
            $currentLorry = Lorry::where('id', $currentLorryId)->first();
            $currentLorry->status = 1;
            $currentLorry->save();

            $newLorry = Lorry::where('id', $newLorryId)->first();
            $newLorry->status = 0;
            $newLorry->save();

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'Van switched successfully.',
                'data' => $newLorry
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //Task
    public function gettask(Request $request)
    {
        try {
            $data = $request->all();
            
            // Check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if (empty($driver)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            
            // Validate trip
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }

            // Process tasks
            $tasks = Task::where('driver_id', $driver->id)
                ->where('date', now()->format('Y-m-d'))
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhereNull('trip_id');
                })
                ->with(['customer' => function($query) {
                    $query->with(['activefoc', 'specialprice']);
                }])
                ->with(['invoice' => function($query) {
                    $query->with(['invoicedetail.product']);
                }])
                ->get();

            // Prepare task data
            $taskData = [];
            foreach ($tasks as $task) {
                $customer = $task->customer;
                
                if ($customer && $customer->id) {
                    // Calculate customer credit
                    $creditData = $this->calculateCustomerCredit(
                        $customer->id, 
                        now()
                    );
                    
                    // Get products with special prices
                    $products = Product::where('status', 1)
                        ->with(['batches' => function($query) {
                            $query->active()
                                ->fefo()
                                ->select('id', 'product_id', 'batch_code', 'expiry_date', 'quantity');
                        }])
                        ->get()
                        ->map(function($product) use ($customer) {
                            // Check for special price
                            $specialPrice = SpecialPrice::where('customer_id', $customer->id)
                                ->where('product_id', $product->id)
                                ->where('status', 1)
                                ->first();
                            
                            $availableBatches = $product->batches->map(function($batch) {
                                return [
                                    'id' => $batch->id,
                                    'batch_code' => $batch->batch_code,
                                    'expiry_date' => $batch->expiry_date,
                                    'quantity' => $batch->quantity,
                                    'days_to_expiry' => $batch->days_to_expiry
                                ];
                            });
                            
                            return [
                                'id' => $product->id,
                                'code' => $product->unit_code,
                                'name' => $product->name,
                                'price' => $specialPrice ? $specialPrice->price : $product->price,
                                'has_special_price' => !empty($specialPrice),
                                'available_batches' => $availableBatches,
                                'total_available' => $availableBatches->sum('quantity')
                            ];
                        });
                    
                    // Get group company info
                    $groupCompany = null;
                    if ($customer->group && is_array($customer->group) && count($customer->group) > 0) {
                        $groupCompany = \DB::table('companies')
                            ->where('companies.group_id', $customer->group[0])
                            ->select('companies.*')
                            ->first();
                    }
                    
                    $taskData[] = [
                        'id' => $task->id,
                        'date' => $task->date,
                        'sequence' => $task->sequence,
                        'status' => $task->status,
                        'based' => $task->based,
                        'invoice' => $task->invoice ? [
                            'id' => $task->invoice->id,
                            'invoiceno' => $task->invoice->invoiceno,
                            'date' => $task->invoice->date,
                            'paymentterm' => $task->invoice->paymentterm,
                            'details' => $task->invoice->invoicedetail->map(function($detail) {
                                return [
                                    'id' => $detail->id,
                                    'product_id' => $detail->product_id,
                                    'product_name' => $detail->product->name ?? null,
                                    'quantity' => $detail->quantity,
                                    'price' => $detail->price,
                                    'totalprice' => $detail->totalprice,
                                    'batch_id' => $detail->product_batch_id,
                                    'batch_code' => $detail->batch->batch_code ?? null
                                ];
                            })
                        ] : null,
                        'customer' => [
                            'id' => $customer->id,
                            'code' => $customer->code,
                            'company' => $customer->company,
                            'phone' => $customer->phone,
                            'billing_address' => $customer->billing_address,
                            'delivery_address' => $customer->delivery_address,
                            'credit' => $creditData['credit'],
                            'paid' => $creditData['paid'],
                            'total_invoiced' => $creditData['totalprice'],
                            'group' => $customer->group,
                            'group_description' => $customer->group_description,
                            'groupcompany' => $groupCompany,
                            'products' => $products,
                            'active_foc' => $customer->activefoc->map(function($foc) {
                                return [
                                    'id' => $foc->id,
                                    'product_id' => $foc->product_id,
                                    'quantity' => $foc->quantity,
                                    'achievequantity' => $foc->achievequantity,
                                    'remaining' => $foc->quantity - $foc->achievequantity,
                                    'startdate' => $foc->startdate,
                                    'enddate' => $foc->enddate
                                ];
                            })
                        ]
                    ];
                }
            }

            // Get inventory balance for the lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $trip->lorry_id)
                ->first();
            
            $stockData = [];
            if ($inventoryBalance && $inventoryBalance->batches) {
                $batchIds = array_keys($inventoryBalance->batches);
                $batches = ProductBatch::with('product')
                    ->whereIn('id', $batchIds)
                    ->get();
                
                foreach ($batches as $batch) {
                    $stockData[] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'product_id' => $batch->product_id,
                        'product_name' => $batch->product->name ?? null,
                        'product_code' => $batch->product->unit_code ?? null,
                        'quantity' => $inventoryBalance->batches[$batch->id],
                        'expiry_date' => $batch->expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry
                    ];
                }
            }

            $hasTasks = count($taskData) > 0;
            
            return response()->json([
                'result' => $hasTasks,
                'message' => __LINE__ . $this->message_separator . 
                    ($hasTasks ? 'api.message.task_found' : 'api.message.task_not_found'),
                'data' => [
                    'task' => $hasTasks ? $taskData : null,
                    'stock' => $stockData,
                    'trip' => [
                        'id' => $trip->id,
                        'date' => $trip->date,
                        'lorry_id' => $trip->lorry_id,
                        'cash' => $trip->cash
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            \Log::error('gettask error: ' . $e->getMessage());
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function gettaskpage(Request $request)
    {
        try {
            $data = $request->all();
            $size = $data['size'] ?? 20;
            
            // Check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if (empty($driver)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            
            // Validate trip
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }

            // Process tasks with pagination
            $tasks = Task::where('driver_id', $driver->id)
                ->where('date', now()->format('Y-m-d'))
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhereNull('trip_id');
                })
                ->with(['customer' => function($query) {
                    $query->with(['activefoc', 'specialprice']);
                }])
                ->with(['invoice' => function($query) {
                    $query->with(['invoicedetail' => function($q) {
                        $q->with(['product:id,name,price', 'batch:id,batch_code,expiry_date']);
                    }]);
                }])
                ->paginate($size);

            // Prepare task data
            $taskData = [];
            foreach ($tasks as $task) {
                $customer = $task->customer;
                
                if ($customer && $customer->id) {
                    // Calculate customer credit
                    $creditData = $this->calculateCustomerCredit(
                        $customer->id, 
                        now()
                    );
                    
                    // Get products with special prices and batch info
                    $products = Product::where('status', 1)
                        ->with(['batches' => function($query) {
                            $query->active()
                                ->fefo()
                                ->select('id', 'product_id', 'batch_code', 'expiry_date', 'quantity');
                        }])
                        ->get()
                        ->map(function($product) use ($customer) {
                            $specialPrice = SpecialPrice::where('customer_id', $customer->id)
                                ->where('product_id', $product->id)
                                ->where('status', 1)
                                ->first();
                            
                            $availableBatches = $product->batches->map(function($batch) {
                                return [
                                    'id' => $batch->id,
                                    'batch_code' => $batch->batch_code,
                                    'expiry_date' => $batch->expiry_date,
                                    'quantity' => $batch->quantity,
                                    'days_to_expiry' => $batch->days_to_expiry
                                ];
                            });
                            
                            return [
                                'id' => $product->id,
                                'unit_code' => $product->unit_code,
                                'name' => $product->name,
                                'price' => $specialPrice ? $specialPrice->price : $product->price,
                                'has_special_price' => !empty($specialPrice),
                                'available_batches' => $availableBatches,
                                'total_available' => $availableBatches->sum('quantity')
                            ];
                        });
                    
                    // Get group company info
                    $groupCompany = null;
                    if ($customer->group && is_array($customer->group) && count($customer->group) > 0) {
                        $groupCompany = \DB::table('companies')
                            ->where('companies.group_id', $customer->group[0])
                            ->select('companies.*')
                            ->first();
                    }
                    
                    $taskData[] = [
                        'id' => $task->id,
                        'date' => $task->date,
                        'sequence' => $task->sequence,
                        'status' => $task->status,
                        'based' => $task->based,
                        'invoice' => $task->invoice ? [
                            'id' => $task->invoice->id,
                            'invoiceno' => $task->invoice->invoiceno,
                            'date' => $task->invoice->date,
                            'paymentterm' => $task->invoice->paymentterm,
                            'chequeno' => $task->invoice->chequeno,
                            'remark' => $task->invoice->remark,
                            'details' => $task->invoice->invoicedetail->map(function($detail) {
                                return [
                                    'id' => $detail->id,
                                    'product_id' => $detail->product_id,
                                    'product_code' => $detail->product->code ?? null,
                                    'product_name' => $detail->product->name ?? null,
                                    'quantity' => $detail->quantity,
                                    'price' => $detail->price,
                                    'totalprice' => $detail->totalprice,
                                    'batch_id' => $detail->product_batch_id,
                                    'batch_code' => $detail->batch->batch_code ?? null,
                                    'remark' => $detail->remark
                                ];
                            })
                        ] : null,
                        'customer' => [
                            'id' => $customer->id,
                            'code' => $customer->code,
                            'company' => $customer->company,
                            'phone' => $customer->phone,
                            'billing_address' => $customer->billing_address,
                            'delivery_address' => $customer->delivery_address,
                            'credit' => $creditData['credit'],
                            'paid' => $creditData['paid'],
                            'total_invoiced' => $creditData['totalprice'],
                            'group' => $customer->group,
                            'group_description' => $customer->group_description,
                            'groupcompany' => $groupCompany,
                            'products' => $products,
                            'active_foc' => $customer->activefoc->map(function($foc) {
                                return [
                                    'id' => $foc->id,
                                    'product_id' => $foc->product_id,
                                    'quantity' => $foc->quantity,
                                    'achievequantity' => $foc->achievequantity,
                                    'remaining' => $foc->quantity - $foc->achievequantity,
                                    'startdate' => $foc->startdate,
                                    'enddate' => $foc->enddate
                                ];
                            })
                        ]
                    ];
                }
            }

            // Get inventory balance for the lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->first();
            
            $stockData = [];
            if ($inventoryBalance && $inventoryBalance->batches) {
                $batchIds = array_keys($inventoryBalance->batches);
                $batches = ProductBatch::with('product')
                    ->whereIn('id', $batchIds)
                    ->get();
                
                foreach ($batches as $batch) {
                    $stockData[] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'product_id' => $batch->product_id,
                        'product_name' => $batch->product->name ?? null,
                        'product_code' => $batch->product->unit_code ?? null,
                        'quantity' => $inventoryBalance->batches[$batch->id],
                        'expiry_date' => $batch->expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry
                    ];
                }
            }

            $hasTasks = count($taskData) > 0;
            
            // Prepare pagination data
            $paginationData = [
                'current_page' => $tasks->currentPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
                'last_page' => $tasks->lastPage(),
                'from' => $tasks->firstItem(),
                'to' => $tasks->lastItem(),
                'has_more_pages' => $tasks->hasMorePages()
            ];
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 
                    ($hasTasks ? 'api.message.task_found' : 'api.message.task_not_found'),
                'data' => [
                    'tasks' => $taskData,
                    'pagination' => $paginationData,
                    'stock' => $stockData,
                    'trip' => [
                        'id' => $trip->id,
                        'date' => $trip->date,
                        'lorry_id' => $trip->lorry_id,
                        'cash' => $trip->cash
                    ]
                ]
            ], 200);

        } catch (Exception $e) {
            \Log::error('gettaskpage error: ' . $e->getMessage());
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function starttask(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $task = Task::where('id',$data['task_id'])->first();
            if(empty($task)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                    'data' => null
                ], 400);
            }else{
                if($task->status == 8){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
                if($task->status == 1){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_in_progress',
                        'data' => null
                    ], 400);
                }
            }
            //process
            $task->status = 1;
            $task->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.task_had_been_started_successfully',
                'data' => $task
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function canceltask(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validate
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'task_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $task = Task::where('id',$data['task_id'])->first();
            if(empty($task)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                    'data' => null
                ], 400);
            }else{
                if($task->status == 8){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
            }
            //process
            $task->status = 9;
            $task->save();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled_successfully',
                'data' => $task
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getproduct(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            if(isset($data['customer_id'])){
                $customer = Customer::where('id', $data['customer_id'])->first();
                if(empty($customer)){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                        'data' => null
                    ], 400);
                }
            }
            //process
            if(isset($data['customer_id'])){
                $product = DB::table('products')
                ->leftJoin('special_prices', function($join) use($data)
                    {
                        $join->on('special_prices.customer_id','=',DB::raw("'".$data['customer_id']."'"));
                        $join->on('special_prices.product_id', '=', 'products.id');
                        $join->on('special_prices.status', '=', DB::raw("'1'"));
                    })
                ->where('products.status','1')
                ->select('products.id','products.code','products.name',DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                ->get();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.product_found',
                    'data' => $product
                ], 200);
            }else{
                $product = DB::table('products')
                ->where('products.status','1')
                ->select('products.id','products.code','products.name',DB::raw('products.price as "price"'))
                ->get();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.product_found',
                    'data' => $product
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getproductInfoFromCode(Request $request){
        try{
            $data = $request->all();
            //check session
            $productCode = $request->input('product_code');
            if(!$productCode){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Product code is required',
                    'data' => null
                ], 401);
            }

            $product = ProductBatch::with('product')->where('batch_code', $productCode)->first();
            if($product){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'Product Found.',
                    'data' => $product
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'Product Not Found',
                    'data' => null
                ], 200);
            }      
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getcustomer(Request $request, $id = null){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            
            if ($id){
                $customers = Customer::select('customers.*', 'assigns.sequence')
                    ->join('assigns', 'customers.id', '=', 'assigns.customer_id')
                    ->where('assigns.driver_id', $driver->id)
                    ->where('assigns.status', Assign::STATUS_ACTIVE)
                    ->where('customers.status', 1)
                    ->where('customers.id', $id)
                    ->orderBy('assigns.sequence', 'asc')
                    ->get();
            }else{
                $customers = Customer::select('customers.*', 'assigns.sequence')
                    ->join('assigns', 'customers.id', '=', 'assigns.customer_id')
                    ->where('assigns.driver_id', $driver->id)
                    ->where('assigns.status', Assign::STATUS_ACTIVE)
                    ->where('customers.status', 1)
                    ->orderBy('assigns.sequence', 'asc')
                    ->get();
            }
            
            // Add credit amount for each customer
            foreach ($customers as $customer) {
                // Calculate credit amount using the same logic as calculateCustomerCredit
                $creditData = $this->calculateCustomerCredit($customer->id, now());
                $customer->credit_amount = $creditData['credit'];
                $customer->total_credit_invoiced = $creditData['totalprice'];
                $customer->total_credit_paid = $creditData['paid'];
            }
            
            //process
            if($customers->isNotEmpty()){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.customer_found',
                    'data' => $customers
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.customer_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerdetail(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id', $data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null], 400);
            }
            //process
            $customer->customerdetail = DB::select("select i.date,i.id,'Invoice' as type, i.invoiceno as name, sum(COALESCE(id.totalprice,0)) as amount from invoices i left join invoice_details id on i.id = id.invoice_id where i.customer_id = ".$customer->id." group by i.date, i.id, i.invoiceno, i.customer_id union select ip.created_at as date,ip.id, 'Payment' as type, '' as name, ip.amount as amount from invoice_payments ip where ip.customer_id = ".$customer->id.";");
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.customer_found',
                'data' => $customer
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customermakepayment(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }

            // Attribute any InvoicePayment activity log entries below to
            // this driver instead of the "System" fallback (Auth::user() is
            // never set for driver API requests) - cleared automatically by
            // LogApiRequests after this request finishes.
            \App\Models\ActivityLog::actingAs($driver->name);

            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
                'amount' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            $customer = Customer::where('id', $data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            //process
            $invoicepayment = New InvoicePayment();
            $invoicepayment->customer_id = $customer->id;
            $invoicepayment->amount = $data['amount'];
            $invoicepayment->type = 1;
            $invoicepayment->status = 1;
            $invoicepayment->driver_id = $driver->id;
            $invoicepayment->approve_by = $driver->name;
            $invoicepayment->approve_at = date('Y-m-d H:i:s');
            $invoicepayment->save();
            $creditResult = DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$invoicepayment->customer_id.');');
            $invoicepayment->newcredit = round($creditResult[0]->credit ?? 0, 2);
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.payment_insert_successfully_found',
                'data' => $invoicepayment
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerinvoice(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|numeric',
                'invoice_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $invoice = Invoice::where('customer_id', $data['customer_id'])
            ->where('id', $data['invoice_id'])
            ->with('invoicedetail.product')
            ->with('customer')
            ->with('driver')
            ->with('invoicepayment')
            ->first();
            if(empty($invoice)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_not_found',
                    'data' => null
                ], 200);
            }else{
               
               
                  
             try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                
                if($credit)
                {
                    $invoice->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoice->newcredit  = 0;
            }
            
               
               //$invoice->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');')[0]->credit,2);
               
               
               
                $invoice->customer->groupcompany = DB::table('companies')
                ->where('companies.group_id', explode(',', $invoice->customer->group ?? '')[0] ?? null)
                ->select('companies.*')
                ->first() ?? null;
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.invoice_found',
                    'data' => $invoice
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function customerpayment(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|numeric',
            'payment_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        //process
        $invoicepayment = InvoicePayment::where('customer_id', $data['customer_id'])->where('id', $data['payment_id'])->with('customer')->first();
        if(empty($invoicepayment)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_payment_not_found',
                'data' => null
            ], 200);
        }else{
            
            
              try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoicepayment->updated_at.'",'.$invoicepayment->customer_id.');');
                
                if($credit)
                {
                    $invoicepayment->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoicepayment->newcredit  = 0;
            }
            
            //$invoicepayment->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.$invoicepayment->created_at.'",'.$invoicepayment->customer_id.');')[0]->credit,2);
            
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_payment_found',
                'data' => $invoicepayment
            ], 200);
        }
    }

    public function getInvoice(Request $request, $customer_id = null)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Start building the query
            $query = Invoice::where('driver_id', $driver->id)
                ->whereDate('date', '>=', now()->subDays(6)->startOfDay());

            // Apply customer filter if customer_id is provided
            if ($customer_id) {
                $query->where('customer_id', $customer_id);
            }

            // Get sales invoices
            $invoices = $query->with([
                    'customer:id,company,phone,paymentterm',
                    'invoicedetail.product:id,name',
                    'invoicedetail.batch:id,batch_code',
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get driver's current trip ID
            $driverTripId = $driver->trip_id ?? null;

            // Define cancellable statuses
            $cancellableStatuses = [Invoice::STATUS_COMPLETED];

            // Format the response
            $formattedInvoices = $invoices->map(function($invoice) use ($driver,$driverTripId, $cancellableStatuses) {
                
                // Check if this specific invoice can be cancelled
                $allowCancel = true;
                $allowEdit = false;

                if($invoice->trip_uuid == $driver->trip_id){
                    $allowEdit = true;
                }
                // Rule 1: Driver must have an active trip
                if (!$driverTripId) {
                    $allowCancel = false;
                }
                
                // Rule 2: Invoice must belong to the same trip as driver's current trip
                // If invoice has no trip_id OR doesn't match driver's current trip, cannot cancel
                if (!$invoice->trip_id || $driverTripId != $invoice->trip_id) {
                    $allowCancel = false;
                }
                
                // Rule 3: Invoice must be in a cancellable status
                if (isset($invoice->status) && !in_array($invoice->status, $cancellableStatuses)) {
                    $allowCancel = false;
                }
                 
                
                return [
                    'id' => $invoice->id,
                    'invoiceno' => $invoice->invoiceno,
                    'date' => $invoice->date,
                    'customer_id' => $invoice->customer_id,
                    'customer' => [
                        'id' => $invoice->customer_id,
                        'name' => $invoice->customer->company ?? 'N/A',
                        'paymentterm' => $invoice->customer->paymentterm ?? '',
                        'phone' => $invoice->customer->phone ?? '',
                    ],                    
                    'paymentterm' => $invoice->paymentterm,
                    'status' => $invoice->getStatusTextAttribute(),
                    'allow_edit' => $allowEdit, // This field indicates if the invoice can be cancelled
                    'remark' => $invoice->remark,
                    'total' => number_format($invoice->total, 3),
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s'),
                    'items_count' => $invoice->invoicedetail->count(),
                    'items' => $invoice->invoicedetail->map(function($detail) {
                        return [
                            'product_id' => $detail->product_id,
                            'batch_code' => $detail->batch->batch_code ?? null,
                            'batch_id' => $detail->product_batch_id,
                            'product_name' => optional($detail->product)->name ?? 'N/A',
                            'quantity' => (float) $detail->quantity,
                            'price' => (float) $detail->price,
                            'total' => (float) $detail->totalprice,
                            'total_formatted' => number_format($detail->totalprice, 3)
                        ];
                    })->toArray(),
                    'pdf_url' => $this->getinvoicepdf($invoice->id)
                ];
            });

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Sales invoices retrieved successfully',
                'data' => [
                    'count' => $invoices->count(),
                    'invoices' => $formattedInvoices->toArray()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving sales invoices: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }
    public function getInvoiceById(Request $request, $id)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Get sales invoice with proper authorization check
            $invoice = Invoice::where('driver_id', $driver->id)
                ->where('id', $id)
                ->first();

            if (!$invoice) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Sales order not found.',
                    'data' => null
                ], 200);
            }

            // Get driver's current trip ID
            $driverTripId = $driver->trip_id;
            
            $allowCancel = true;
                
                // Rule 1: Driver must have an active trip
            if (!$driverTripId) {
                $allowCancel =  false;
            }

            // Rule 2: Invoice must belong to the same trip as driver's current trip
            if (!$invoice->trip_id || $driverTripId != $invoice->trip_id) {
                $allowCancel =  false;
            }
            // Rule 3: Invoice must be in a cancellable status
            $cancellableStatuses = [Invoice::STATUS_COMPLETED];

            if (isset($invoice->status) && !in_array($invoice->status, $cancellableStatuses)) {
                $allowCancel =  false;
            }
            
            // Format the response
            $formattedInvoice = [
                'id' => $invoice->id,
                    'invoiceno' => $invoice->invoiceno,
                    'date' => $invoice->date,
                    'customer_id' => $invoice->customer_id,
                    'customer' => [
                        'id' => $invoice->customer_id,
                        'name' => $invoice->customer->company ?? 'N/A',
                        'paymentterm' => $invoice->customer->paymentterm ?? '',
                        'phone' => $invoice->customer->phone ?? '',
                    ],                    
                    'paymentterm' => $invoice->paymentterm,
                    'status' => $invoice->getStatusTextAttribute(),
                    'remark' => $invoice->remark,
                    'total' => number_format($invoice->total, 3),
                    'created_at' => $invoice->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $invoice->updated_at->format('Y-m-d H:i:s'),
                    'items_count' => $invoice->invoicedetail->count(),
                    'items' => $invoice->invoicedetail->map(function($detail) {
                        return [
                            'product_id' => $detail->product_id,
                            'product_name' => optional($detail->product)->name ?? 'N/A',
                            'quantity' => (float) $detail->quantity,
                            'price' => (float) $detail->price,
                            'total' => (float) $detail->totalprice,
                            'total_formatted' => number_format($detail->totalprice, 3)
                        ];
                    })->toArray(),
                    'pdf_url' => $this->getinvoicepdf($invoice->id)
            ];

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Sales order retrieved successfully',
                'data' => $formattedInvoice
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving sales order: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

	public function getinvoicepdf($invoice_id)
    {
        try {

            $invoice = Invoice::where('id',$invoice_id)
                ->with('customer')
                ->with('driver')
                ->with('invoicedetail.product')
                ->first();

            if (empty($invoice)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Invoice not found',
                    'data' => null
                ], 200);
            }

            // Rendering this PDF (DomPDF + a credit calculation + a companies
            // lookup) is expensive and was being redone on every single call,
            // including once per invoice in list endpoints. An invoice's PDF
            // never changes unless the invoice itself does, so cache it - but
            // invoice_details has no $touches back to invoices, so adding/
            // editing/removing a line item does NOT bump invoices.updated_at.
            // Keying on that alone served a stale PDF (missing newly-added
            // items) forever once cached. Fold in the detail rows' own count
            // and latest updated_at too, so any change to the line items
            // themselves also invalidates the cache.
            $detailsSignature = $invoice->invoicedetail->count()
                . '_' . optional($invoice->invoicedetail->max('updated_at'))->timestamp;
            $cacheKey = 'invoice_pdf_' . $invoice->id . '_' . $invoice->updated_at->timestamp . '_' . $detailsSignature;

            $generatePdf = function () use ($invoice) {
                $min = 450;
                $each = 23;
                $height = (count($invoice['invoicedetail']) * $each) + $min;
                $creditData = $this->calculateCustomerCredit(
                    $invoice->customer_id,
                    $invoice->updated_at
                );

                $invoice->newcredit = round($creditData['credit'] ?? 0, 2);

                $invoice->customer->groupcompany = DB::table('companies')
                ->where('companies.group_id', explode(',', $invoice->customer->group ?? '')[0] ?? null)
                ->select('companies.*')
                ->first() ?? null;


                // DomPDF is memory-hungry rendering invoices with many detail rows,
                // overrunning PHP's default 128MB limit and returning a bare 500
                // (a memory FatalError is a \Error, not \Exception, so the catch
                // below never sees it). Raise the ceiling for this request.
                ini_set('memory_limit', '512M');

                $pdf = Pdf::loadView('invoices.print', [
                    'invoice' => $invoice
                ]);

                $pdf->setPaper(array(0, 0, 300, $height), 'portrait')
                    ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

                return base64_encode($pdf->output());
            };

            try {
                return Cache::remember($cacheKey, now()->addDays(30), $generatePdf);
            } catch (\Throwable $cacheError) {
                // Cache layer unavailable (e.g. storage/framework/cache not
                // writable) - still return the PDF, just without caching it,
                // instead of letting the whole request fail.
                return $generatePdf();
            }

        } catch (\Throwable $e) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function addinvoice(Request $request)
    {
        try {
            $data = $request->all();

            // Check driver session
            $driver = Driver::where('session', $request->header('session'))->first();
            if (empty($driver)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                    'data' => null
                ], 401);
            }

            // Attribute any Invoice/InvoiceDetail activity log entries below
            // to this driver instead of the "System" fallback (Auth::user()
            // is never set for driver API requests) - cleared automatically
            // by LogApiRequests after this request finishes.
            \App\Models\ActivityLog::actingAs($driver->name);

            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }

            // $trip is still needed below for lorry_id (inventory balance lookup)
            $trip = Trip::where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }

            $inventoryCountRecord = InventoryCount::where('driver_id', $driver->id)
                ->where('trip_id', $driver->trip_id)
                ->where('status', InventoryCount::STATUS_APPROVED)
                ->first();

            if($inventoryCountRecord){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Driver have completed inventory count, cannot add new invoice, You may continue in next new trip.',
                    'data' => null
                ], 200);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'invoiceno' => 'nullable|string|max:255',
                'date' => 'date_format:Y-m-d',
                'customer_id' => 'required|numeric',
                'paymentterm' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'cheque_no' => 'nullable|string',
                'invoicedetail' => 'required|array|min:1',
                'invoicedetail.*.product_id' => 'required|numeric',
                'invoicedetail.*.product_batch_id' => 'required|numeric',
                'invoicedetail.*.quantity' => 'required|numeric|min:1',
                'invoicedetail.*.price' => 'required|numeric|min:0',
                'invoicedetail.*.discount' => 'nullable|numeric|min:0',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            // Validate customer
            $customer = Customer::where('id', $data['customer_id'])->first();
            if (empty($customer)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            
            // Get driver's inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->first();
            
            // Categorize items
            $itemsToProcess = [];     // Items that exist in inventory (will deduct stock)
            $itemsToIgnore = [];       // Items not in inventory (will not deduct stock)
            $invalidBatches = [];      // Invalid product batches (product_id mismatch)
            
            foreach ($data['invoicedetail'] as $index => $item) {
                // Check if product batch exists and matches product_id
                $productBatch = ProductBatch::where('id', $item['product_batch_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();
                
                if (empty($productBatch)) {
                    // Invalid batch - product_id doesn't match
                    $invalidBatches[] = [
                        'product_id' => $item['product_id'],
                        'product_batch_id' => $item['product_batch_id'],
                        'quantity_requested' => $item['quantity'],
                        'price' => $item['price'],
                        'error' => 'Invalid product batch or product ID mismatch'
                    ];
                    continue;
                }
                
                // Check if batch exists in driver's inventory
                $availableQuantity = 0;
                $hasInventory = false;
                
                if ($inventoryBalance) {
                    $availableQuantity = $inventoryBalance->getBatchQuantity($item['product_batch_id']);
                    if ($availableQuantity > 0) {
                        $hasInventory = true;
                    }
                }
                
                if ($hasInventory) {
                    // Item exists in inventory - will deduct stock
                    $itemsToProcess[] = [
                        'index' => $index,
                        'item' => $item,
                        'productBatch' => $productBatch,
                        'availableQuantity' => $availableQuantity
                    ];
                } else {
                    // Item not in inventory - will be ignored (no stock deduction)
                    $itemsToIgnore[] = [
                        'product_id' => $item['product_id'],
                        'product_batch_id' => $item['product_batch_id'],
                        'batch_code' => $productBatch->batch_code,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'discount' => $item['discount'] ?? 0,
                        'available_quantity' => $availableQuantity,
                        'reason' => 'Product not found in driver inventory'
                    ];
                }
            }
            
            // If there are invalid batches, return error
            if (!empty($invalidBatches)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Invalid product batches found',
                    'data' => [
                        'invalid_batches' => $invalidBatches
                    ]
                ], 400);
            }
            
            // Process invoice even if no items in inventory (allow empty invoice creation)
            // But at least ensure there are some valid items (either in inventory or not)
            if (empty($itemsToProcess) && empty($itemsToIgnore)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No valid invoice items found',
                    'data' => null
                ], 400);
            }
            
            // Process invoice
            $runningno = Code::where('code', 'invoicerunningnumber')->first();
            if ($runningno) {
                $runningno->value = intval($runningno->value) + 1;
                $runningno->save();
            }
            
            DB::beginTransaction();

            // Create invoice - retry with a freshly generated number if the
            // invoiceno collides with a concurrently-created one (race condition guard).
            // The app predicts the number client-side (offline / getNextInvoiceNumber),
            // so two devices can submit the SAME requested invoiceno - use it only if
            // it's still free, otherwise fall back to a freshly generated one.
            $invoice = null;
            $attempts = 0;
            $requestedInvoiceNo = !empty($data['invoiceno']) ? $data['invoiceno'] : null;
            $useRequestedInvoiceNo = true;

            while (!$invoice) {
                $attempts++;

                try {
                    $invoice = DB::transaction(function () use ($data, $driver, $requestedInvoiceNo, $useRequestedInvoiceNo) {
                        $invoiceno = ($useRequestedInvoiceNo && $requestedInvoiceNo && !Invoice::where('invoiceno', $requestedInvoiceNo)->exists())
                            ? $requestedInvoiceNo
                            : Invoice::generateInvoiceNumber($driver->id);

                        $newInvoice = new Invoice();
                        $newInvoice->date = $data['date'] ?? date('Y-m-d H:i:s');
                        $newInvoice->invoiceno = $invoiceno;
                        $newInvoice->customer_id = $data['customer_id'];
                        $newInvoice->driver_id = $driver->id;
                        $newInvoice->paymentterm = $data['paymentterm'];
                        $newInvoice->chequeno = $data['cheque_no'] ?? null;
                        $newInvoice->remark = $data['remark'] ?? null;
                        $newInvoice->trip_uuid = $driver->trip_id;
                        $newInvoice->status = Invoice::STATUS_COMPLETED;
                        $newInvoice->save();

                        return $newInvoice;
                    });
                } catch (\Illuminate\Database\QueryException $e) {
                    // A unique-constraint collision (race between the existence check
                    // above and the insert) - drop the requested number and retry
                    // with a freshly generated one.
                    if ($attempts >= 5 || !Invoice::isDuplicateInvoiceNumberException($e)) {
                        throw $e;
                    }
                    $useRequestedInvoiceNo = false;
                }
            }

            $totalprice = 0;
            
            // Process items that exist in inventory (deduct stock)
            foreach ($itemsToProcess as $validItem) {
                $item = $validItem['item'];
                $productBatch = $validItem['productBatch'];
                
                // Create invoice detail
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $item['product_id'];
                $invoicedetail->product_batch_id = $item['product_batch_id'];
                $invoicedetail->quantity = $item['quantity'];
                $invoicedetail->price = $item['price'];
                $invoicedetail->discount = $item['discount'] ?? 0;
                $invoicedetail->totalprice = ($item['quantity'] * $item['price']) - ($item['discount'] ?? 0);
                $invoicedetail->remark = $item['remark'] ?? null;
                $invoicedetail->deducted_from_inventory = true;
                $invoicedetail->save();

                $totalprice += $invoicedetail->totalprice;

                // Create inventory transaction record
                $inventoryTransaction = new InventoryTransaction();
                $inventoryTransaction->type = 2;
                $inventoryTransaction->product_id = $item['product_id'];
                $inventoryTransaction->batch_id = $item['product_batch_id'];
                $inventoryTransaction->quantity = -$item['quantity'];
                $inventoryTransaction->date = now();
                $inventoryTransaction->lorry_id = $trip->lorry_id;
                $inventoryTransaction->user = $driver->name;
                $inventoryTransaction->remark = 'Invoice #' . $invoice->invoiceno . ' - Sale of product';
                $inventoryTransaction->save();
                
                // Update lorry inventory balance
                if ($inventoryBalance) {
                    $inventoryBalance->updateBatchQuantity(
                        $item['product_batch_id'], 
                        $item['quantity'], 
                        'subtract'
                    );
                }
            }
            
            // Process items not in inventory (create invoice details but NO stock deduction)
            foreach ($itemsToIgnore as $ignoredItem) {
                // Create invoice detail WITHOUT deducting stock
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $ignoredItem['product_id'];
                $invoicedetail->product_batch_id = $ignoredItem['product_batch_id'];
                $invoicedetail->quantity = $ignoredItem['quantity'];
                $invoicedetail->price = $ignoredItem['price'];
                $invoicedetail->discount = $ignoredItem['discount'] ?? 0;
                $invoicedetail->totalprice = ($ignoredItem['quantity'] * $ignoredItem['price']) - ($ignoredItem['discount'] ?? 0);
                $invoicedetail->remark = $ignoredItem['remark'] ?? null;
                $invoicedetail->deducted_from_inventory = false;
                $invoicedetail->save();

                $totalprice += $invoicedetail->totalprice;

                // NO stock deduction for ignored items
                // NO inventory transaction created
                // NO lorry inventory balance update
            }

            // Create invoice payment for cash transactions
            if ($data['paymentterm'] == 1) {
                $invoicepayment = new InvoicePayment();
                $invoicepayment->invoice_id = $invoice->id;
                // Cash (non-credit) -> auto-sync as its own single-invoice AR Payment.
                $invoicepayment->payment_batch_id = (string) \Illuminate\Support\Str::uuid();
                $invoicepayment->autocount_status = InvoicePayment::AC_PENDING;
                $invoicepayment->type = 1;
                $invoicepayment->customer_id = $invoice->customer_id;
                $invoicepayment->amount = $totalprice;
                $invoicepayment->status = 1;
                $invoicepayment->driver_id = $driver->id;
                $invoicepayment->approve_by = $driver->name;
                $invoicepayment->approve_at = date('Y-m-d H:i:s');
                $invoicepayment->save();
            }
            
            // Update task status
            Task::where('customer_id', $data['customer_id'])
                ->where('driver_id', $driver->id)
                ->update(['status' => 8]);
            
            DB::commit();
            
            // Get the complete invoice with relationships
            $iv = Invoice::where('id', $invoice->id)
                ->with([
                    'customer', 
                    'driver', 
                    'invoicedetail.product', 
                    'invoicedetail.batch'
                ])
                ->first();
                        
            // Calculate customer credit
            $creditData = $this->calculateCustomerCredit(
                $iv->customer_id, 
                $iv->updated_at
            );
            
            $iv->newcredit = round($creditData['credit'] ?? 0, 2);
            
            // Prepare response message
            $message = 'Invoice created successfully';
            if (!empty($itemsToIgnore)) {
                $message .= ' - ' . count($itemsToIgnore) . ' item(s) added without inventory deduction (not found in stock)';
            }
            if (!empty($itemsToProcess)) {
                $message .= ' - ' . count($itemsToProcess) . ' item(s) deducted from inventory';
            }
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $message,
                'data' => $iv
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    public function editInvoice(Request $request, $id)
    {
        try {
            $data = $request->all();

            // Check driver session
            $driver = Driver::where('session', $request->header('session'))->first();
            if (empty($driver)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                    'data' => null
                ], 401);
            }

            // Attribute any Invoice/InvoiceDetail activity log entries below
            // to this driver instead of the "System" fallback (Auth::user()
            // is never set for driver API requests) - cleared automatically
            // by LogApiRequests after this request finishes.
            \App\Models\ActivityLog::actingAs($driver->name);

            // Find existing invoice
            $invoice = Invoice::where('id', $id)
                ->where('driver_id', $driver->id)
                ->first();
            
            if (empty($invoice)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Invoice not found or you do not have permission to edit',
                    'data' => null
                ], 404);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
                'invoiceno' => 'nullable|string|max:255',
                'date' => 'nullable|date_format:Y-m-d',
                'customer_id' => 'required|numeric',
                'paymentterm' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'cheque_no' => 'nullable|string',
                // Allowed empty on purpose: driver can save an invoice with
                // zero items (returning all stock to the lorry) as an
                // intermediate step before adding the corrected items back
                // in a follow-up edit.
                'invoicedetail' => 'present|array',
                'invoicedetail.*.product_id' => 'required|numeric',
                'invoicedetail.*.product_batch_id' => 'required|numeric',
                'invoicedetail.*.quantity' => 'required|numeric|min:1',
                'invoicedetail.*.price' => 'required|numeric|min:0',
                'invoicedetail.*.discount' => 'nullable|numeric|min:0',
                'invoicedetail.*.id' => 'nullable|numeric'
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            // Validate customer
            $customer = Customer::where('id', $data['customer_id'])->first();
            if (empty($customer)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_customer',
                    'data' => null
                ], 400);
            }
            
            // Get trip for inventory updates - only if the driver currently
            // has one active. Querying "latest by date" regardless of type
            // could return an already-ended trip's lorry if the driver is
            // between trips, incorrectly applying inventory changes there.
            $trip = !empty($driver->trip_id)
                ? Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first()
                : null;

            DB::beginTransaction();
            
            // ============================================
            // STEP 1: RESTORE OLD INVENTORY QUANTITIES
            // (Only for items that were originally in inventory)
            // ============================================
            $oldDetails = InvoiceDetail::where('invoice_id', $invoice->id)->get();
            $inventoryBalance = null;
            
            if ($trip) {
                $inventoryBalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->first();
            }
            
            foreach ($oldDetails as $oldDetail) {
                // Whether this line item's stock was actually deducted from the
                // lorry inventory at creation time (persisted flag - NOT inferred
                // from the current balance, since a batch that's since been fully
                // used up gets its key removed from InventoryBalance::batches,
                // making it indistinguishable from "was never deducted").
                $hasInventory = $oldDetail->deducted_from_inventory && $inventoryBalance;

                if ($hasInventory) {

                    // Restore inventory balance
                    if ($inventoryBalance) {
                        $inventoryBalance->updateBatchQuantity(
                            $oldDetail->product_batch_id,
                            $oldDetail->quantity,
                            'add' // Add back the quantity
                        );
                    }
                    
                    // Reverse inventory transaction (create reversal record)
                    $reversalTransaction = new InventoryTransaction();
                    $reversalTransaction->type = 1; // Stock In (reversal)
                    $reversalTransaction->product_id = $oldDetail->product_id;
                    $reversalTransaction->batch_id = $oldDetail->product_batch_id;
                    $reversalTransaction->quantity = $oldDetail->quantity;
                    $reversalTransaction->date = now();
                    $reversalTransaction->lorry_id = $trip->lorry_id ?? null;
                    $reversalTransaction->user = $driver->name;
                    $reversalTransaction->remark = 'Invoice # [' . $invoice->invoiceno . '] - Edit reversal (removed items)';
                    $reversalTransaction->save();
                }
            }
            
            // Delete old invoice details
            InvoiceDetail::where('invoice_id', $invoice->id)->delete();
            
            // ============================================
            // STEP 2: CATEGORIZE NEW ITEMS
            // ============================================
            $itemsToProcess = [];     // Items that exist in inventory (will deduct stock)
            $itemsToIgnore = [];       // Items not in inventory (will not deduct stock)
            $invalidBatches = [];      // Invalid product batches
            
            foreach ($data['invoicedetail'] as $index => $item) {
                // Check if product batch exists and matches product_id
                $productBatch = ProductBatch::where('id', $item['product_batch_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();
                
                if (empty($productBatch)) {
                    $invalidBatches[] = [
                        'product_id' => $item['product_id'],
                        'product_batch_id' => $item['product_batch_id'],
                        'error' => 'Invalid product batch or product ID mismatch'
                    ];
                    continue;
                }
                
                // Check if batch exists in driver's inventory
                $hasInventory = false;
                $availableQuantity = 0;
                
                if ($inventoryBalance) {
                    $availableQuantity = $inventoryBalance->getBatchQuantity($item['product_batch_id']);
                    if ($availableQuantity >= $item['quantity']) {
                        $hasInventory = true;
                    }
                }
                
                if ($hasInventory) {
                    // Item exists in inventory with sufficient stock
                    $itemsToProcess[] = [
                        'index' => $index,
                        'item' => $item,
                        'productBatch' => $productBatch,
                        'availableQuantity' => $availableQuantity
                    ];
                } else {
                    // Item not in inventory or insufficient stock - will be added without deduction
                    $itemsToIgnore[] = [
                        'product_id' => $item['product_id'],
                        'product_batch_id' => $item['product_batch_id'],
                        'batch_code' => $productBatch->batch_code,
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'discount' => $item['discount'] ?? 0,
                        'available_quantity' => $availableQuantity,
                        'reason' => $availableQuantity > 0 ? 'Insufficient stock' : 'Product not found in driver inventory'
                    ];
                }
            }
            
            // If there are invalid batches, return error
            if (!empty($invalidBatches)) {
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Invalid product batches found',
                    'data' => [
                        'invalid_batches' => $invalidBatches
                    ]
                ], 400);
            }
            
            // Note: itemsToProcess and itemsToIgnore both being empty here is
            // allowed - it means invoicedetail was submitted as [] on purpose
            // (STEP 1 above already restored the old items' stock to the
            // lorry). Any submitted-but-invalid batch would already have
            // triggered the invalidBatches return above instead of reaching
            // this point empty-handed.

            // ============================================
            // STEP 3: UPDATE INVOICE HEADER
            // ============================================
            $invoice->date = $data['date'] ?? date('Y-m-d H:i:s');
            $invoice->customer_id = $data['customer_id'];
            $invoice->paymentterm = $data['paymentterm'];
            $invoice->chequeno = $data['cheque_no'] ?? null;
            $invoice->remark = $data['remark'] ?? null;
            
            // Only update invoice number if provided and different
            if (!empty($data['invoiceno']) && $data['invoiceno'] != $invoice->invoiceno) {
                // Check if new invoice number already exists
                $existingInvoice = Invoice::where('invoiceno', $data['invoiceno'])->first();
                if ($existingInvoice && $existingInvoice->id != $invoice->id) {
                    DB::rollBack();
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Invoice number already exists',
                        'data' => null
                    ], 400);
                }
                $invoice->invoiceno = $data['invoiceno'];
            }
            
            $invoice->save();
            
            // ============================================
            // STEP 4: CREATE NEW INVOICE DETAILS
            // ============================================
            $totalprice = 0;
            
            // Process items that exist in inventory (deduct stock)
            foreach ($itemsToProcess as $validItem) {
                $item = $validItem['item'];
                $productBatch = $validItem['productBatch'];
                
                // Create invoice detail
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $item['product_id'];
                $invoicedetail->product_batch_id = $item['product_batch_id'];
                $invoicedetail->quantity = $item['quantity'];
                $invoicedetail->price = $item['price'];
                $invoicedetail->discount = $item['discount'] ?? 0;
                $invoicedetail->totalprice = ($item['quantity'] * $item['price']) - ($item['discount'] ?? 0);
                $invoicedetail->remark = $item['remark'] ?? null;
                $invoicedetail->deducted_from_inventory = true;
                $invoicedetail->save();

                $totalprice += $invoicedetail->totalprice;

                // Create inventory transaction record
                $inventoryTransaction = new InventoryTransaction();
                $inventoryTransaction->type = 2; // Stock Out
                $inventoryTransaction->product_id = $item['product_id'];
                $inventoryTransaction->batch_id = $item['product_batch_id'];
                $inventoryTransaction->quantity = -$item['quantity'];
                $inventoryTransaction->date = now();
                $inventoryTransaction->lorry_id = $trip->lorry_id ?? null;
                $inventoryTransaction->user = $driver->name;
                $inventoryTransaction->remark = 'Invoice #' . $invoice->invoiceno . ' - Sale of product (edited)';
                $inventoryTransaction->save();
                
                // Update lorry inventory balance
                if ($inventoryBalance) {
                    $inventoryBalance->updateBatchQuantity(
                        $item['product_batch_id'],
                        $item['quantity'],
                        'subtract'
                    );
                }
            }
            
            // Process items not in inventory (create invoice details but NO stock deduction)
            foreach ($itemsToIgnore as $ignoredItem) {
                // Create invoice detail WITHOUT deducting stock
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $ignoredItem['product_id'];
                $invoicedetail->product_batch_id = $ignoredItem['product_batch_id'];
                $invoicedetail->quantity = $ignoredItem['quantity'];
                $invoicedetail->price = $ignoredItem['price'];
                $invoicedetail->discount = $ignoredItem['discount'] ?? 0;
                $invoicedetail->totalprice = ($ignoredItem['quantity'] * $ignoredItem['price']) - ($ignoredItem['discount'] ?? 0);
                $invoicedetail->remark = $ignoredItem['remark'] ?? null;
                $invoicedetail->deducted_from_inventory = false;
                $invoicedetail->save();

                $totalprice += $invoicedetail->totalprice;

                // NO stock deduction for ignored items
                // NO inventory transaction created
                // NO lorry inventory balance update
            }

            // ============================================
            // STEP 5: UPDATE INVOICE PAYMENT
            // ============================================
            if ($data['paymentterm'] == 1) {
                // Check if payment already exists
                $existingPayment = InvoicePayment::where('invoice_id', $invoice->id)->first();
                
                if ($existingPayment) {
                    // Update existing payment. Re-queue it for AutoCount so the
                    // amount change is pushed as an edit to the existing OR.
                    // The customer may also have been changed on this edit -
                    // keep the payment pointing at the invoice's (new)
                    // customer, otherwise the OR is issued to the old debtor.
                    $existingPayment->customer_id = $invoice->customer_id;
                    $existingPayment->amount = $totalprice;
                    if (empty($existingPayment->payment_batch_id)) {
                        $existingPayment->payment_batch_id = (string) \Illuminate\Support\Str::uuid();
                    }
                    $existingPayment->autocount_status       = InvoicePayment::AC_PENDING;
                    $existingPayment->autocount_auto_retried = false;
                    $existingPayment->save();
                } else {
                    // Create new payment
                    $invoicepayment = new InvoicePayment();
                    $invoicepayment->invoice_id = $invoice->id;
                    // Cash (non-credit) -> auto-sync as its own single-invoice AR Payment.
                    $invoicepayment->payment_batch_id = (string) \Illuminate\Support\Str::uuid();
                    $invoicepayment->autocount_status = InvoicePayment::AC_PENDING;
                    $invoicepayment->type = 1;
                    $invoicepayment->customer_id = $invoice->customer_id;
                    $invoicepayment->amount = $totalprice;
                    $invoicepayment->status = 1;
                    $invoicepayment->driver_id = $driver->id;
                    $invoicepayment->approve_by = $driver->name;
                    $invoicepayment->approve_at = date('Y-m-d H:i:s');
                    $invoicepayment->save();
                }
            } else {
                // If payment term changed from cash to credit, delete existing payment
                InvoicePayment::where('invoice_id', $invoice->id)->delete();
            }
            
            DB::commit();
            
            // ============================================
            // STEP 6: RETURN UPDATED INVOICE
            // ============================================
            $updatedInvoice = Invoice::where('id', $invoice->id)
                ->with([
                    'customer', 
                    'driver', 
                    'invoicedetail.product', 
                    'invoicedetail.batch'
                ])
                ->first();
                        
            // Calculate customer credit
            $creditData = $this->calculateCustomerCredit(
                $updatedInvoice->customer_id, 
                $updatedInvoice->updated_at
            );
            
            $updatedInvoice->newcredit = round($creditData['credit'] ?? 0, 2);
            
            // Prepare response message
            $message = 'Invoice updated successfully';
            
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $message,
                'data' => $updatedInvoice
            ], 200);
            
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getInvoiceNo(Request $request)
    {
    try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $invoiceno = Invoice::getNextInvoiceNumber($driver->id);

            return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'Invoice No retrieved.',
                    'data' => $invoiceno
                ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    private function calculateCustomerCredit($customerId, $asOfDate)
    {
        try {
            // Get total invoiced amount for CREDIT payment term invoices only
            $totalInvoiced = Invoice::where('invoices.customer_id', $customerId)
                ->where('invoices.paymentterm', Invoice::PAYMENT_TERM_CREDIT) // Only credit payment term (2)
                ->where('invoices.updated_at', '<=', $asOfDate)
                ->join('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
                ->selectRaw('COALESCE(SUM(invoice_details.totalprice), 0) as total')
                ->value('total'); 

            // Get total paid amount for CREDIT invoices only (completed payments)
            $totalPaid = InvoicePayment::where('customer_id', $customerId)
                ->where('status', 1) // Completed payment
                ->where('approve_at', '<=', $asOfDate)
                ->whereHas('invoice', function($query) {
                    $query->where('paymentterm', Invoice::PAYMENT_TERM_CREDIT) // Only payments for credit invoices
                        ->where('status', Invoice::STATUS_COMPLETED);
                })
                ->sum('amount') ?? 0;

            $outstandingBalance = $totalInvoiced - $totalPaid;

            return [
                'totalprice' => round($totalInvoiced, 2),
                'paid' => round($totalPaid, 2),
                'credit' => round($outstandingBalance, 2)
            ];
            
        } catch (\Exception $e) {
            Log::error('Credit calculation failed: ' . $e->getMessage());
            return [
                'totalprice' => 0,
                'paid' => 0,
                'credit' => 0
            ];
        }
    }
    
      public function invoicepdf(Request $request)
	{
	    try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'invoice_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            $id = $data['invoice_id'];
            
            
            $invoice = Invoice::where('id',$id)
            ->with('customer')
            ->with('driver')
            ->with('invoicedetail.product')
            ->first();
    
            $min = 450;
            $each = 23;
            $height = (count($invoice['invoicedetail']) * $each) + $min;
    
            try
            {
                $credit = $this->calculateCustomerCredit($invoice->customer_id, $invoice->updated_at);

                if($credit)
                {
                    $invoice->newcredit = round($credit['credit'] ?? 0, 2);
                }
    
            }
            catch(Exception $ex)
            {
                 $invoice->newcredit  = 0;
            }
            $invoice->customer->groupcompany = DB::table('companies')
            ->where('companies.group_id', explode(',', $invoice->customer->group ?? '')[0] ?? null)
            ->select('companies.*')
            ->first() ?? null;
            
            // DomPDF is memory-hungry rendering invoices with many detail rows,
            // overrunning PHP's default 128MB limit and returning a bare 500
            // (a memory FatalError is a \Error, not \Exception, so the catch
            // below never sees it). Raise the ceiling for this request.
            ini_set('memory_limit', '512M');

              $pdf = Pdf::loadView('invoices.print', array(
                    'invoice' => $invoice
                ));

            $pdf->setPaper(array(0, 0, 300, $height), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);
    
            $invoiceFilename = 'invoice-' . $invoice->invoiceno . '.pdf';
            $path = 'invoices-pdf/' . $invoiceFilename;
            
            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);

            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
        
	  
	}
	

    public function getcustomerinvoice(Request $request, $id = null)
    {
        try {
            // Get the authenticated driver from session
            $driver = Driver::where('session', $request->header('session'))->first();
            if (!$driver) {
                return response()->json([
                    'result' => false,
                    'message' => 'Invalid session',
                    'data' => null,
                ], 401);
            }
            
            // Get all customer IDs assigned to this driver
            $assignedCustomerIds = Assign::where('driver_id', $driver->id)
                ->active()
                ->whereHas('customer', function ($q) { $q->where('status', 1); })
                ->pluck('customer_id')
                ->toArray();
            
            if (empty($assignedCustomerIds)) {
                return response()->json([
                    'result' => false,
                    'message' => 'No customers assigned to this driver',
                    'data' => null,
                ], 404);
            }
            
            // Validate customer if ID provided
            if ($id) {
                // Check if customer is assigned to this driver
                $customer = Customer::where('id', $id)
                    ->whereIn('id', $assignedCustomerIds)
                    ->first();
                    
                if (!$customer) {
                    return response()->json([
                        'result' => false,
                        'message' => 'Customer not found or not assigned to this driver',
                        'data' => null,
                    ], 404);
                }
            }
            
            // Build query to get all invoices with their details and payments
            $query = Invoice::with(['invoicedetail', 'invoicepayment'])
                ->whereIn('customer_id', $assignedCustomerIds) // Only show invoices for assigned customers
                ->orderBy('date', 'desc');
            
            if ($id) {
                $query->where('customer_id', $id);
            }
            
            $invoices = $query->get();
            
            if ($invoices->isEmpty()) {
                $message = $id ? 'No invoices found for this customer' : 'No invoices found for your assigned customers';
                return response()->json([
                    'result' => false,
                    'message' => $message,
                    'data' => null,
                ], 404);
            }
            
            if ($id) {
                // Single customer response
                $invoiceData = [];
                $totalOutstanding = 0;
                
                foreach ($invoices as $invoice) {
                    $totalAmount = $invoice->invoicedetail->sum('totalprice');
                    
                    // Check if invoice has any completed payment
                    $hasCompletedPayment = $invoice->invoicepayment->contains(function($payment) {
                        return $payment->status == 1;
                    });
                    
                    // Only add to outstanding if no completed payment
                    if (!$hasCompletedPayment) {
                        $totalOutstanding += $totalAmount;
                    }
                    
                    // Prepare payment records
                    $paymentRecords = [];
                    foreach ($invoice->invoicepayment as $payment) {
                        $paymentRecords[] = [
                            'id' => $payment->id,
                            'amount' => (float) $payment->amount,
                            'formatted_amount' => 'RM ' . number_format($payment->amount, 2),
                            'status' => $payment->status,
                            'status_text' => $payment->status == 1 ? 'Completed' : 'Pending',
                            'payment_date' => $payment->approve_at ?? $payment->created_at,
                            'remark' => $payment->remark,
                            'chequeno' => $payment->chequeno
                        ];
                    }
                    
                    $invoiceData[] = [
                        'id' => $invoice->id,
                        'invoice_no' => $invoice->invoiceno,
                        'date' => $invoice->date,
                        'payment_term' => $this->getPaymentTermText($invoice->paymentterm),
                        'payment_term_code' => $invoice->paymentterm,
                        'total_amount' => (float) $totalAmount,
                        'formatted_amount' => 'RM ' . number_format($totalAmount, 3),
                        'is_paid' => $hasCompletedPayment,
                        'invoice_payments' => $paymentRecords,
                        'payment_count' => count($paymentRecords)
                    ];
                }
                
                return response()->json([
                    'result' => true,
                    'message' => 'Invoices retrieved successfully',
                    'data' => [
                        'customer_id' => (int) $id,
                        'customer_name' => $customer->company,
                        'total_outstanding' => (float) $totalOutstanding,
                        'formatted_outstanding' => 'RM ' . number_format($totalOutstanding, 2),
                        'invoice_count' => count($invoiceData),
                        'invoices' => $invoiceData
                    ]
                ], 200);
                
            } else {
                // All customers response - Group by customer
                $allCustomersData = [];
                $totalOutstandingAll = 0;
                
                // Group invoices by customer
                $groupedByCustomer = $invoices->groupBy('customer_id');
                
                foreach ($groupedByCustomer as $customerId => $customerInvoices) {
                    $customerModel = Customer::find($customerId);
                    if (!$customerModel) continue;
                    
                    $customerInvoicesData = [];
                    $customerTotalOutstanding = 0;
                    
                    foreach ($customerInvoices as $invoice) {
                        $totalAmount = $invoice->invoicedetail->sum('totalprice');
                        
                        // Check if invoice has any completed payment
                        $hasCompletedPayment = $invoice->invoicepayment->contains(function($payment) {
                            return $payment->status == 1;
                        });
                        
                        // Only add to outstanding if no completed payment
                        if (!$hasCompletedPayment) {
                            $customerTotalOutstanding += $totalAmount;
                            $totalOutstandingAll += $totalAmount;
                        }
                        
                        // Prepare payment records
                        $paymentRecords = [];
                        foreach ($invoice->invoicepayment as $payment) {
                            $paymentRecords[] = [
                                'id' => $payment->id,
                                'amount' => (float) $payment->amount,
                                'formatted_amount' => 'RM ' . number_format($payment->amount, 2),
                                'status' => $payment->status,
                                'status_text' => $payment->status == 1 ? 'Completed' : 'Pending',
                                'payment_date' => $payment->approve_at ?? $payment->created_at,
                                'remark' => $payment->remark,
                                'chequeno' => $payment->chequeno
                            ];
                        }
                        
                        $customerInvoicesData[] = [
                            'id' => $invoice->id,
                            'invoice_no' => $invoice->invoiceno,
                            'date' => $invoice->date,
                            'payment_term' => $this->getPaymentTermText($invoice->paymentterm),
                            'payment_term_code' => $invoice->paymentterm,
                            'total_amount' => (float) $totalAmount,
                            'formatted_amount' => 'RM ' . number_format($totalAmount, 3),
                            'is_paid' => $hasCompletedPayment,
                            'invoice_payments' => $paymentRecords,
                            'payment_count' => count($paymentRecords)
                        ];
                    }
                    
                    $allCustomersData[] = [
                        'customer_id' => $customerId,
                        'customer_name' => $customerModel->company,
                        'total_outstanding' => (float) $customerTotalOutstanding,
                        'formatted_outstanding' => 'RM ' . number_format($customerTotalOutstanding, 2),
                        'invoice_count' => count($customerInvoicesData),
                        'invoices' => $customerInvoicesData
                    ];
                }
                
                return response()->json([
                    'result' => true,
                    'message' => 'Invoices retrieved successfully',
                    'data' => [
                        'total_outstanding_all' => (float) $totalOutstandingAll,
                        'formatted_outstanding_all' => 'RM ' . number_format($totalOutstandingAll, 2),
                        'total_customers' => count($allCustomersData),
                        'customers' => $allCustomersData
                    ]
                ], 200);
            }
            
        } catch (Exception $e) {
            return response()->json([
                'result' => false,
                'message' => 'Failed to retrieve invoices: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }
    // Helper method to get payment term text
    private function getPaymentTermText($paymentTerm)
    {
        switch($paymentTerm) {
            case Invoice::PAYMENT_TERM_CASH:
                return 'Cash';
            case Invoice::PAYMENT_TERM_CREDIT:
                return 'Credit';
            case Invoice::PAYMENT_TERM_ONLINE:
                return 'Online Payment';
            case Invoice::PAYMENT_TERM_TNG:
                return 'Touch n Go';
            case Invoice::PAYMENT_TERM_CHEQUE:
                return 'Cheque';
            default:
                return 'Unknown';
        }
    }

    public function addpayment(Request $request)
    {
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null,
                    'color_code' => ''
                ], 401);
            }

            // Attribute any Invoice/InvoicePayment activity log entries below
            // to this driver instead of the "System" fallback (Auth::user()
            // is never set for driver API requests) - cleared automatically
            // by LogApiRequests after this request finishes.
            \App\Models\ActivityLog::actingAs($driver->name);

            //validation
            $validator = Validator::make($request->all(), [
                'date' => 'date_format:Y-m-d H:i:s',
                'customer_id' => 'required|numeric',
                'type' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'invoice_ids' => 'required|array|min:1', // Changed to array
                'invoice_ids.*' => 'numeric|exists:invoices,id', // Validate each invoice ID exists
                'cheque_no' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null,
                ], 400);
            }
            
            $customer = Customer::where('id', $data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null,
                ], 400);
            }
            
            //process
            DB::beginTransaction();
            
            $createdPayments = [];
            $invoiceIds = $data['invoice_ids'];

            // If invoice_ids is a string (comma-separated), convert to array
            if (!is_array($invoiceIds)) {
                $invoiceIds = explode(',', $invoiceIds);
            }

            // One payment event = one AutoCount AR Payment. Tag every row created
            // here with the same batch id so they later sync as a single OR that
            // knocks off all these invoices. If ANY invoice in the batch is on
            // Credit term the whole batch is put on 'hold' and only syncs after a
            // manual "Sync" click from web; otherwise it auto-syncs.
            $paymentBatchId  = (string) \Illuminate\Support\Str::uuid();
            $batchIsCredit   = Invoice::whereIn('id', $invoiceIds)
                ->where('paymentterm', Invoice::PAYMENT_TERM_CREDIT)
                ->exists();
            // Auto-sync only a single-invoice, non-credit payment. A payment that
            // spans multiple invoices is held for a manual web "Sync" click (client:
            // these are almost always credit); it still becomes ONE OR knocking off
            // all its invoices, it just waits for the click.
            $isMultiInvoice  = count($invoiceIds) > 1;
            $autocountStatus = ($batchIsCredit || $isMultiInvoice)
                ? InvoicePayment::AC_HOLD : InvoicePayment::AC_PENDING;

            // Get the total amount from the request or calculate from invoices
            $totalAmount = isset($data['amount']) ? $data['amount'] : 0;
            $amountPerInvoice = $totalAmount / count($invoiceIds);
        
            foreach ($invoiceIds as $index => $invoiceId) {
            // Get the invoice to calculate its total amount
            $invoice = Invoice::with('invoicedetail')->where('id', $invoiceId)->first();
            
            if (empty($invoice)) {
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invoice not found for ID: '.$invoiceId,
                    'data' => null,
                ], 400);
            }

            // The payment is booked under $data['customer_id']; every invoice it
            // knocks off must belong to that same customer. A cross-customer
            // knock-off is rejected by AutoCount on sync ("belongs to debtor X,
            // not Y"), so block it here before any row is written.
            if ((int) $invoice->customer_id !== (int) $data['customer_id']) {
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invoice '.$invoice->invoiceno.' does not belong to the selected customer',
                    'data' => null,
                ], 400);
            }

            // Calculate invoice total amount
            $invoiceTotal = $invoice->invoicedetail ? $invoice->invoicedetail->sum("totalprice") : 0;
            
            // Check if invoice already has approved payment
            $existingPayment = InvoicePayment::where('invoice_id', $invoiceId)
                ->where('status', 1)
                ->first();
                
            if ($existingPayment) {
                DB::rollBack();
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invoice '.$invoice->invoiceno.' already has an approved payment',
                    'data' => null,
                ], 400);
            }
            
            $invoicepayment = new InvoicePayment();
            $invoicepayment->invoice_id = $invoiceId;
            $invoicepayment->payment_batch_id = $paymentBatchId;
            $invoicepayment->autocount_status = $autocountStatus;
            $invoicepayment->type = $data['type'];
            $invoicepayment->customer_id = $data['customer_id'];
            
            // If amount is provided in request, distribute it among invoices
                // Otherwise use the invoice total amount
                if (isset($data['amount']) && $data['amount'] > 0) {
                    // For the last invoice, use remaining amount to avoid floating point issues
                    if ($index === array_key_last($invoiceIds)) {
                        $paymentAmount = $totalAmount - ($amountPerInvoice * (count($invoiceIds) - 1));
                    } else {
                        $paymentAmount = $amountPerInvoice;
                    }
                    $invoicepayment->amount = round($paymentAmount, 2);
                } else {
                    $invoicepayment->amount = $invoiceTotal;
                }
                
                $invoicepayment->status = 1;
                $invoicepayment->chequeno = $data['cheque_no'] ?? null;
                $invoicepayment->driver_id = $driver->id;
                $invoicepayment->approve_by = $driver->name;
                $invoicepayment->approve_at = date('Y-m-d H:i:s');
                $invoicepayment->remark = $data['remark'] ?? null;
                
                // Set custom date if provided
                if (isset($data['date'])) {
                    $invoicepayment->created_at = $data['date'];
                }
                
                $invoicepayment->save();
                
                // Update invoice status to paid (status = 1)
                $invoice->status = 1;
                $invoice->save();
                
                $createdPayments[] = $invoicepayment;
            }
            
            DB::commit();
            
            // Prepare response data
            $responsePayments = [];
            foreach ($createdPayments as $payment) {
                $iv = InvoicePayment::where('id', $payment->id)->first();
                $iv['payment_no'] = sprintf('PR%05d', $iv->id);
                
                // Calculate credit for each payment
                try {
                    $credit = DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');');
                    if($credit) {
                        $iv->newcredit = round($credit[0]->credit, 2);
                    } else {
                        $iv->newcredit = 0;
                    }
                } catch(Exception $ex) {
                    $iv->newcredit = 0;
                }
                
                $responsePayments[] = $iv;
            }
            
            // If only one payment was created, return single object for backward compatibility
            $responseData = count($responsePayments) == 1 ? $responsePayments[0] : $responsePayments;
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.payment_add_successfully',
                'data' => $responseData,
                'total_payments' => count($responsePayments)
            ], 200);
            
        } catch(Exception $e){
            DB::rollBack();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
      public function paymentpdf(Request $request)
	{
	    try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            $validator = Validator::make($request->all(), [
                'payment_id' => 'required|numeric'
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            
            $id = $data['payment_id'];
            
            
            $invoice = InvoicePayment::where('id',$id)
                    ->with('customer')
                    ->first();
    
            if (empty($invoice)) {
                abort('404');
            }
    
            $min = 450;
            $each = 23;
    
            try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.$invoice->updated_at.'",'.$invoice->customer_id.');');
                
                if($credit)
                {
                    $invoice->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $invoice->newcredit  = 0;
            }
            
            $invoice->customer->groupcompany = DB::table('companies')
            ->where('companies.group_id', explode(',', $invoice->customer->group ?? '')[0] ?? null)
            ->select('companies.*')
            ->first() ?? null;
            
            // DomPDF is memory-hungry rendering receipts, overrunning PHP's
            // default 128MB limit and returning a bare 500 (a memory FatalError
            // is a \Error, not \Exception, so the catch below never sees it).
            // Raise the ceiling for this request.
            ini_set('memory_limit', '512M');

            $pdf = Pdf::loadView('invoice_payments.print', array(
                'invoice' => $invoice
            ));


            $pdf->setPaper(array(0, 0, 300, $min), 'portrait')->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);
            
            $invoiceFilename = 'payment-' . $invoice->id . '.pdf';
            $path = 'payments/' . $invoiceFilename;
            
            Storage::disk('public')->put($path, $pdf->output());
            $url = url($path);
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.load_success',
                'data' => $url
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
        
	  
	}
	
	
    public function getstock(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            //process
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)
            ->leftjoin('products','products.id','=','inventory_balances.product_id')
            ->get(['inventory_balances.id','inventory_balances.quantity','inventory_balances.product_id','products.name'])->toarray();
            if(count($inventorybalance) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.no_stock_found',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.stock_found',
                    'data' => $inventorybalance
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function listotherdriver(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null], 401);
            }
            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $drivers = Trip::where('driver_id','!=',$driver->id)
            ->select('driver_id','drivers.name','drivers.employeeid')
            ->groupby('driver_id','drivers.name','drivers.employeeid')
            ->havingRaw('(count(driver_id) % 2) > 0')
            ->leftjoin('drivers','drivers.id','=','trips.driver_id')
            ->get()->toarray();
            if(count($drivers) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.no_driver_found',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.driver_found',
                    'data' => $drivers
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function transferstock(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
        if (empty($trip)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|numeric',
            'transferdetail' => 'present|array',
            'transferdetail.*.product_id' => 'required|numeric',
            'transferdetail.*.quantity' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id',$data['driver_id'])->first();
        if(empty($todriver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        if (empty($todriver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.selected_driver_trip_had_not_started',
                'data' => null
            ], 400);
        }
        $totrip = Trip::where('driver_id', $data['driver_id'])->where('uuid', $todriver->trip_id)->where('type', Trip::START_TRIP)->first();
        if (empty($totrip)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.selected_driver_trip_had_not_started',
                'data' => null
            ], 400);
        }
        //process
        try{

            DB::beginTransaction();
            foreach($data['transferdetail'] as $td){
                $product = Product::where('id',$td['product_id'])->first();
                if(empty($product)){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_product',
                        'data' => null
                    ], 400);
                }
                $inventorytransfer = New InventoryTransfer();
                $inventorytransfer->date = date('Y-m-d H:i:s');
                $inventorytransfer->from_driver_id = $trip->driver_id;
                $inventorytransfer->from_lorry_id = $trip->lorry_id;
                $inventorytransfer->to_driver_id = $totrip->driver_id;
                $inventorytransfer->to_lorry_id = $totrip->lorry_id;
                $inventorytransfer->product_id = $td['product_id'];
                $inventorytransfer->quantity = $td['quantity'];
                $inventorytransfer->status = 1;
                $inventorytransfer->save();
            }
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.pending_driver_accept_transfer',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function gettransfer(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null], 401);
            }
            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $request = InventoryTransfer::where('from_driver_id', $driver->id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            ->with('product:id,name')
            ->with('todriver:id,name')
            ->orderby('date','desc')
            ->get(['id','date','status','quantity','product_id','to_driver_id'])
            ->toarray();
            $pending = InventoryTransfer::where('to_driver_id', $driver->id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            // ->where('status', 1)
            ->with('product:id,name')
            ->with('fromdriver:id,name')
            ->orderby('date','desc')
            ->get(['id','date','status','quantity','product_id','from_driver_id'])
            ->toarray();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.transfer_found',
                'data' => [
                    'request' => $request,
                    'pending' => $pending
                ]
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function updatetransfer(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'transfer_id' => 'required|numeric',
            'status' => 'required|numeric|gt:1|lt:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        // $inventorytransfer = InventoryTransfer::where('id', $data['transfer_id'])->where('to_driver_id',$driver->id)->first();
        $inventorytransfer = InventoryTransfer::where('id', $data['transfer_id'])->first();
        if(empty($inventorytransfer)){
            return response()->json([
               'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.transfer_not_found',
                'data' => null
            ], 400);
        }
        if($inventorytransfer->status == 2){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.transfer_already_accepted',
                'data' => null
            ], 400);
        }
        if($inventorytransfer->status == 3){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.transfer_already_rejected',
              'data' => null
            ], 400);
        }
        $fromdriver = Driver::where('id',$inventorytransfer->from_driver_id)->first();
        if(empty($fromdriver)){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.from_driver_not_found',
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id',$inventorytransfer->to_driver_id)->first();
        if(empty($todriver)){
            return response()->json([
              'result' => false,
              'message' => __LINE__.$this->message_separator.'api.message.to_driver_not_found',
                'data' => null
            ], 400);
        }
        //process
        try{

            DB::beginTransaction();
            if($data['status'] == 3){
                $inventorytransfer->status = 3;
                $inventorytransfer->save();
                DB::commit();
                return response()->json([
                   'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.transfer_rejecet_successfully',
                    'data' => null
                ], 200);
            }
            if($data['status'] == 2){
                $inventorytransfer->status = 2;
                $inventorytransfer->save();
                 DB::commit();
                 return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.transfer_accept_successfully',
                     'data' => null
                 ], 200);
            }
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getstocktransaction(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $trip = Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            if($data['date'] > date('Y-m-d H:i:s')){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.date_cannot_be_future_date',
                    'data' => null
                ], 400);
            }
            //process
            $inventorytransaction = InventoryTransaction::where('lorry_id',$trip->lorry_id)
            ->leftjoin('products','products.id','=','inventory_transactions.product_id')
            ->where('date','>=',$data['date'])
            ->where('date','<',date('Y-m-d', strtotime("+1 day", strtotime($data['date']))))
            ->orderby('date','desc')
            // ->select('lorry_id','product_id','quantity','type','date');
            ->select('inventory_transactions.id','inventory_transactions.quantity','inventory_transactions.type','inventory_transactions.date','products.name');

            $finalinventorytransaction = InventoryTransaction::where('lorry_id',$trip->lorry_id)
            ->leftjoin('products','products.id','=','inventory_transactions.product_id')
            ->where('date','<',$data['date'])
            ->groupby('inventory_transactions.product_id','products.id','products.name')
            // ->select('lorry_id','product_id',DB::raw('sum(quantity) as quantity'),DB::raw('0 as type'),DB::raw('"'.$data['date'].'" as date'))
            ->select(DB::raw('0 as id'),DB::raw('sum(inventory_transactions.quantity) as quantity'),DB::raw('0 as type'),DB::raw('"'.$data['date'].'" as date'),'products.name')
            ->union($inventorytransaction)
            ->orderby('date','desc')
            ->get()
            ->toarray();
            if(count($finalinventorytransaction) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.transaction_not_found',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.transaction_found',
                    'data' => $finalinventorytransaction
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function listalldriver(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $driver = Driver::where('id','!=',$driver->id)->get()->toarray();
            if(count($driver) == 0){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_driver',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.driver_found',
                    'data' => $driver
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    //NA
    public function getdrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json(['result' => false, 'message' => 'Session not found', 'data' => null], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
        }
        $messages = array(
            'driver_id.required' => 'Driver ID is required',
        );
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }
        $fromdriver = Driver::where('id',$data['driver_id'])->first();
        if(empty($fromdriver)){
            return response()->json(['result' => false,'message' => 'Driver not found', 'data' => null], 400);
        }
        //process
        $fromdrivertrip = Trip::where('driver_id', $fromdriver->id)->orderby('date','desc')->first();
        if(!empty($fromdrivertrip)){
            if($fromdrivertrip->type == 2){
                //Take from assign & invoice
                $assigns = Assign::where('driver_id', $fromdriver->id)
                ->active()
                ->whereHas('customer', function ($q) { $q->where('status', 1); })
                ->orderby('sequence','asc')
                ->select('customer_id','sequence',DB::RAW('0 as invoice_id'));
                $task = Invoice::where('driver_id', $fromdriver->id)
                ->where('status',0)
                ->where('date',date('Y-m-d'))
                ->select('customer_id',DB::RAW('0 as sequence'),DB::RAW('id as invoice_id'))
                ->union($assigns)
                ->with('customer')
                ->get()->toarray();
                if(empty($task)){
                    return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
                }else{
                    return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
                }
            }else{
                //Take from task
                $task = Task::where('driver_id',$fromdriver->id)
                ->wherein('status',[0,1])
                ->select('customer_id','sequence','invoice_id')
                ->with('customer')
                ->get()->toarray();
                if(empty($task)){
                    return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
                }else{
                    return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
                }
            }
        }else{
            //Take from assign & invoice
            $assigns = Assign::where('driver_id', $fromdriver->id)
            ->active()
            ->whereHas('customer', function ($q) { $q->where('status', 1); })
            ->orderby('sequence','asc')
            ->select('customer_id','sequence',DB::RAW('0 as invoice_id'));
            $task = Invoice::where('driver_id', $fromdriver->id)
            ->where('status',0)
            ->where('date',date('Y-m-d'))
            ->select('customer_id',DB::RAW('0 as sequence'),DB::RAW('id as invoice_id'))
            ->union($assigns)
            ->with('customer')
            ->get()->toarray();
            if(empty($task)){
                return response()->json(['result' => false,'message' => 'Task not found', 'data' => null], 200);
            }else{
                return response()->json(['result' => true,'message' => 'Task found', 'data' => $task], 200);
            }
        }
    }

    //NA
    public function pulldrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json(['result' => false, 'message' => 'Session not found', 'data' => null], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
        }
        $messages = array(
            'driver_id.required' => 'Driver ID is required',
            'transferdetail.*.customer_id.required' => 'Customer ID is required',
        );
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required',
            'transferdetail.*.customer_id' => 'required',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => $validator->errors(),
                'data' => null
            ], 400);
        }
        try{
            if(count($data['transferdetail']) == 0){
                return response()->json(['result' => false, 'message' => 'Invalid format, transfer detail is empty', 'data' => null], 400);
            }
        }
        catch(Exception $e){
            return response()->json(['result' => false, 'message' => 'Invalid format', 'data' => null], 400);
        }
        $fromdriver = Driver::where('id', $data['driver_id'])->first();
        if(empty($fromdriver)){
            return response()->json(['result' => false,'message' => 'Driver not found', 'data' => null], 400);
        }
        //process
        try{
            DB::beginTransaction();
            foreach($data['transferdetail'] as $key => $c){
                $customer = Customer::where('id',$c['customer_id'])->first();
                if(empty($customer)){
                    DB::rollback();
                    return response()->json(['result' => false,'message' => 'Customer not found', 'data' => null], 400);
                }else{
                    $fromdrivertrip = Trip::where('driver_id', $fromdriver->id)->orderby('date','desc')->first();
                    if(!empty($fromdrivertrip)){
                        if($fromdrivertrip->type == 2){
                            //take from assign & invoice
                            $invoice = Invoice::where('driver_id', $fromdriver->id)
                            ->where('status',0)
                            ->where('date',date('Y-m-d'))
                            ->where('customer_id',$customer->id)
                            ->get()->toarray();
                            if(empty($invoice)){
                                $newtask =  New Task();
                                $newtask->driver_id = $driver->id;
                                $newtask->customer_id = $customer->id;
                                $newtask->status = 0;
                                $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                if(empty($sequence)){
                                    $sequence = 0;
                                }else{
                                    $sequence = $sequence->sequence;
                                }
                                $newtask->sequence =  $sequence + 1;
                                $newtask->date = date('Y-m-d');
                                $newtask->save();
                            }else{
                                foreach($invoice as $i){
                                    $newtask =  New Task();
                                    $newtask->driver_id = $driver->id;
                                    $newtask->customer_id = $customer->id;
                                    $newtask->invoice_id = $i['id'];
                                    $newtask->status = 0;
                                    $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                    if(empty($sequence)){
                                        $sequence = 0;
                                    }else{
                                        $sequence = $sequence->sequence;
                                    }
                                    $newtask->sequence =  $sequence + 1;
                                    $newtask->date = date('Y-m-d');
                                    $newtask->save();
                                }
                            }
                        }else{
                            //take from task
                            $task = Task::where('driver_id',$fromdriver->id)
                            ->wherein('status',[0,1])
                            ->where('customer_id',$customer->id)->first();
                            $newtask =  New Task();
                            $newtask->driver_id = $driver->id;
                            $newtask->customer_id = $customer->id;
                            $newtask->status = 0;
                            $newtask->invoice_id = $task->invoice_id;
                            $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                            if(empty($sequence)){
                                $sequence = 0;
                            }else{
                                $sequence = $sequence->sequence;
                            }
                            $newtask->sequence =  $sequence + 1;
                            $newtask->date = date('Y-m-d');
                            $newtask->save();
                            $task->update(['status' => 9]);
                        }
                    }else{
                        //take from assign & invoice
                        $invoice = Invoice::where('driver_id', $fromdriver->id)
                        ->where('status',0)
                        ->where('date',date('Y-m-d'))
                        ->where('customer_id',$customer->id)
                        ->get()->toarray();
                        if(empty($invoice)){
                            $newtask =  New Task();
                            $newtask->driver_id = $driver->id;
                            $newtask->customer_id = $customer->id;
                            $newtask->status = 0;
                            $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                            if(empty($sequence)){
                                $sequence = 0;
                            }else{
                                $sequence = $sequence->sequence;
                            }
                            $newtask->sequence =  $sequence + 1;
                            $newtask->date = date('Y-m-d');
                            $newtask->save();
                        }else{
                            foreach($invoice as $i){
                                $newtask =  New Task();
                                $newtask->driver_id = $driver->id;
                                $newtask->customer_id = $customer->id;
                                $newtask->invoice_id = $i['id'];
                                $newtask->status = 0;
                                $sequence = Task::where('driver_id',$driver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                                if(empty($sequence)){
                                    $sequence = 0;
                                }else{
                                    $sequence = $sequence->sequence;
                                }
                                $newtask->sequence =  $sequence + 1;
                                $newtask->date = date('Y-m-d');
                                $newtask->save();
                            }
                        }
                    }

                }
            }
            DB::commit();
            return response()->json(['result' => true, 'message' => 'Pulled task successfully', 'data' => null], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json(['result' => false,'message' => $e->getMessage(), 'data' => null], 400);
        }
    }

    public function pushdrivertask(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|numeric',
            'transferdetail' => 'present|array',
            'transferdetail.*.task_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $todriver = Driver::where('id', $data['driver_id'])->first();
        if(empty($todriver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_driver',
                'data' => null
            ], 400);
        }
        //process
        try{
            DB::beginTransaction();
            foreach($data['transferdetail'] as $key => $c){
                $task = Task::where('id',$c['task_id'])->first();
                if(empty($task)){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.invalid_task',
                        'data' => null
                    ], 400);
                }
                if($task->status == 9){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_cancelled',
                        'data' => null
                    ], 400);
                }
                if($task->status == 8){
                    DB::rollback();
                    return response()->json([
                       'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.task_had_been_completed',
                        'data' => null
                    ], 400);
                }
                $sequence = Task::where('driver_id',$todriver->id)->where('date',date('Y-m-d'))->orderby('sequence','desc')->first();
                if(empty($sequence)){
                    $sequence = 0;
                }else{
                    $sequence = $sequence->sequence;
                }
                $task->sequence = $sequence + 1;
                $task->driver_id = $todriver->id;
                $task->status = 0;
                $task->based = 0;
                $task->trip_id = null;
                $task->save();

                $tasktransfer = new TaskTransfer();
                $tasktransfer->date = date("Y-m-d H:i:s");
                $tasktransfer->from_driver_id = $driver->id;
                $tasktransfer->to_driver_id = $todriver->id;
                $tasktransfer->task_id = $c['task_id'];
                $tasktransfer->save();
            }
            DB::commit();
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.push_task_successfully',
                'data' => null
            ], 200);
        }
        catch(Exception $e){
            DB::rollback();
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function listtranfer(Request $request){
        $data = $request->all();
        //check session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        if (empty($driver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                'data' => null
            ], 400);
        }
        //process
        try{
            $tasktransfer = TaskTransfer::where('from_driver_id',$driver->id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            ->with('fromdriver:id,name')
            ->with('todriver:id,name')
            ->with('task.customer')
            ->get()->toArray();
            if(!empty($tasktransfer)){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_transfer_found',
                    'data' => $tasktransfer
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_transfer_not_found',
                    'data' => null
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function dashboard_bk(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            //validation
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            if($data['date'] > date('Y-m-d H:i:s')){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.date_cannot_be_future_date',
                    'data' => null
                ], 400);
            }
            //process
            $sales = DB::Select('select sum(a.totalprice) as sales from(select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by i.id) a')[0]->sales;
            $cash = DB::Select('select coalesce(sum(coalesce(amount,0)),0) as cash from invoice_payments where type = 1 and status = 1 and driver_id = '.$driver->id.' and approve_at >= "'.$data['date'].'" and approve_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            // $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id left join invoice_payments ip on ip.invoice_id = i.id where i.status = 1 and i.date = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and ip.id is null group by i.id ) a')[0]->credit;
            $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 2 group by i.id ) a')[0]->credit;
            $productsold = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id)[0]->productsold;
            $solddetail = DB::select('select p.name, sum(id.quantity) as quantity from invoices i left join invoice_details id on id.invoice_id = i.id left join products p on p.id = id.product_id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $trip = DB::select('select t.id, d.name as driver_name, k.name as kelindan_name, l.lorryno from trips t left join drivers d on d.id = t.driver_id left join kelindans k on k.id = t.kelindan_id left join lorrys l on l.id = t.lorry_id where t.driver_id = '.$driver->id.' and t.type = 1 and t.date >= "'.$data['date'].'" and t.date < "'.$data['date'].' 23:59:59"');
            // $trip = Trip::where('driver_id', $driver->id)
            // ->where('date','>=',$data['date'].' 00:00:00')
            // ->where('date','<',$data['date'].' 23:59:59')
            // ->where('type',1)
            // ->with('driver')
            // ->with('kelindan')
            // ->with('lorry')
            // ->get()
            // ->toArray();
            $result = [
                'sales' => round($sales,2),
                'cash' => round($cash,2),
                'credit' => round($credit,2),
                'productsold' => [
                    'total_quantity' =>round($productsold,2),
                    'details' =>$solddetail
                ],
                'trip' => $trip
            ];
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.get_dashboard_successfully',
                'data' => $result
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
     public function dashboard(Request $request){
        try{
            $data = $request->all();
            //check session
            $driver = Driver::where('session', $request->header('session'))->first();
            if(empty($driver)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            
            $trip = Trip::where('driver_id', $driver->id)->where('uuid',$driver->trip_id)->first();

            // Initialize default empty values
            $invoices = collect(); // Empty collection instead of null
            $salesByPaymentTerm = [
                'cash' => 0,
                'credit' => 0,
                'online_payment' => 0,
                'tng' => 0,
                'cheque' => 0,
            ];
            $productsSold = [];
            $inventoryBalances = []; // Initialize this variable as it was undefined

            if($driver->trip_id != null){
                $invoices = Invoice::where('trip_uuid', $driver->trip_id)
                    ->where('status', Invoice::STATUS_COMPLETED)
                    ->with(['invoicedetail.product', 'invoicepayment'])
                    ->get();

                // Only calculate if invoices collection is not empty
                if($invoices->isNotEmpty()) {
                    // Bucket by the actual payment ledger (invoice_payments), not
                    // invoices.paymentterm - that field is set once at invoice
                    // creation and is never updated when a Credit invoice is later
                    // settled in cash via addpayment(), which caused this dashboard
                    // to disagree with the Daily Sales Report (which already sums
                    // invoice_payments) by the amount of any such invoice.
                    // Only approved payments (status == 1) count - a pending or
                    // rejected row was never actually collected.
                    $payments = $invoices->flatMap(function($invoice) {
                        return $invoice->invoicepayment;
                    })->where('status', 1);

                    $sumByType = function($type) use ($payments) {
                        return round($payments->where('type', $type)->sum('amount'), 2);
                    };

                    $salesByPaymentTerm = [
                        'cash' => $sumByType(Invoice::PAYMENT_TERM_CASH),
                        'credit' => $sumByType(Invoice::PAYMENT_TERM_CREDIT),
                        'online_payment' => $sumByType(Invoice::PAYMENT_TERM_ONLINE),
                        'tng' => $sumByType(Invoice::PAYMENT_TERM_TNG),
                        'cheque' => $sumByType(Invoice::PAYMENT_TERM_CHEQUE),
                    ];

                    $productsSold = $invoices->flatMap(function($invoice) {
                            return $invoice->invoicedetail;
                        })
                        ->groupBy('product_id')
                        ->map(function($details, $productId) {
                            $firstDetail = $details->first();
                            return [
                                'name' => $firstDetail->product ? $firstDetail->product->name : 'Unknown Product',
                                'quantity' => $details->sum('quantity')
                            ];
                        })
                        ->values()
                        ->toArray();
                }
            }
            
            // Handle trip data
            $tripArray = [];
            if($trip){
                if ($trip->type == Trip::END_TRIP) {
                    $end_time = $trip->date;

                    $start_trip = Trip::where('driver_id', $driver->id)
                        ->orderBy('date', 'desc')
                        ->where('type', Trip::START_TRIP)
                        ->first();

                    $start_time = $start_trip->date ?? null;
                    
                    $tripArray = [
                        [
                            'trip_id' => $start_trip->uuid ?? '',
                            'start_time' => $start_time ?? '',
                            'type' => 'Start Trip',
                        ],
                        [
                            'trip_id' => $trip->uuid,
                            'end_time' => $end_time ?? '',
                            'type' => 'End Trip',
                        ]
                    ];
                } else {
                    $start_time = $trip->date;
                    $end_time = null;
                    
                    $tripArray = [
                        [
                            'trip_id' => $trip->uuid,
                            'start_time' => $start_time ?? '',
                            'type' => 'Start Trip',
                        ]
                    ];
                }
            }

            $result = [
                'sales_summary' => [
                    'total_invoices' => $invoices ? $invoices->count() : 0,
                    'by_payment_term' => $salesByPaymentTerm,
                ],
                'productsold' => $productsSold,
                'inventory_balance' => $inventoryBalances, // Fixed variable name (was missing 's')
                'trip' => $tripArray,
                'lorry' => $trip ? $trip->lorry->lorryno : null,
            ];
            
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.get_dashboard_successfully',
                'data' => $result
            ], 200);
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getAllLanguages(Request $request)
    {
        $data = $request->all();
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $languages = MobileTranslationVersion::with('language')->get();

        $translations = [];

        foreach ($languages as $languageVersion) {
            $translations[] = [
                'language' => $languageVersion->language->name, 
                'code'     => $languageVersion->language->code,  
                'version'  => $languageVersion->version,
            ];
        }
        return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator,
                'data' => $translations
            ], 200);
    }

    public function getTranslations(Request $request)
    {
        $data = $request->all();
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        //validation
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
        ]); 
        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        $code = $data['code'];
        $language = Language::where('code', $code)->first();

        if(empty($language)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'Invalid Language Code',
                    'data' => null
                ], 401);
            }
        $version = MobileTranslationVersion::where('language_id', $language->id)->first();
        $translations = MobileTranslation::where('language_id', $language->id)
            ->get()
            ->pluck('value', 'key')
            ->toArray();

        $result = [
            'version' => $version->version,
            'translation' => $translations
        ];

        return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.language_update_successfully',
                'data' => $result
            ], 200);
       
    }   

    public function getAllProduct(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Get special prices
            $specialPrices = SpecialPrice::where('status', 1)
                    ->pluck('price', 'product_id')
                    ->toArray();

            // Get the driver's currently active trip (trip_id is only set
            // while a trip is genuinely active, cleared on end)
            if (empty($driver->trip_id)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No trip found for this driver',
                    'data' => []
                ], 200);
            }
            $latestTrip = Trip::where('driver_id', $driver->id)
                ->where('uuid', $driver->trip_id)
                ->where('type', Trip::START_TRIP)
                ->first();

            if (!$latestTrip) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No trip found for this driver',
                    'data' => []
                ], 200);
            }

            $lorry_id = $latestTrip->lorry_id;

            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorry_id)->first();

            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'No inventory found',
                    'data' => []
                ], 200);
            }

            // Get all batch IDs from the inventory
            $batchIds = array_keys($inventoryBalance->batches);

            // Get all active product batches with their products
            $productBatches = ProductBatch::with(['product' => function($query) {
                    $query->select('id', 'name', 'price', 'status', 'unit_code');
                }])
                ->whereIn('id', $batchIds)
                ->whereHas('product', function($query) {
                    $query->where('status', 1); // Only active products
                })
                ->where('status', 1) // Only active batches
                ->orderBy('expiry_date', 'asc') // FEFO - First Expiry First Out
                ->get();

                // Initialize output array
            $output = [];
            
            // Loop through each batch
            foreach ($productBatches as $batch) {
                // Get quantity from inventory balance
                $quantity = $inventoryBalance->batches[$batch->id] ?? 0;
                
                // Skip if quantity is 0
                if ($quantity <= 0) {
                    continue;
                }
                
                // Get price: use special price if available, otherwise default price
                $price = $specialPrices[$batch->product_id] ?? $batch->product->price;
                
                // Format expiry date
                $expiryDate = $batch->expiry_date ? \Carbon\Carbon::parse($batch->expiry_date)->format('d-m-Y') : null;
                
                // Calculate days to expiry
                $daysToExpiry = $batch->expiry_date ? now()->diffInDays($batch->expiry_date, false) : null;
                
                // Check if expiring soon (within 30 days)
                $isExpiringSoon = $daysToExpiry !== null && $daysToExpiry > 0 && $daysToExpiry <= 30;
                
                // Add to output array (append, not overwrite)
                $output[] = [
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'expiry_date' => $expiryDate,
                    'quantity' => $quantity,
                    'days_to_expiry' => $daysToExpiry,
                    'is_expiring_soon' => $isExpiringSoon,
                    'product' => [
                        'id' => $batch->product->id,
                        'name' => $batch->product->name,
                        'unit_code' => $batch->product->unit_code,
                        'is_special_price' => isset($specialPrices[$batch->product_id]), 
                        'price' => number_format($price, 3),
                    ]
                ];
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product batches retrieved successfully',
                'data' => $output
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function getInventoryBalance(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        
        $latestTrip = Trip::where('driver_id', $driver->id)
                ->where('uuid',$driver->trip_id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();
                
        if (!$latestTrip) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'No trip found for this driver',
                'data' => []
            ], 200);
        }
        
        try {
            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $latestTrip->lorry_id)->first();
            
            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No inventory found for this driver',
                    'data' => []
                ]);
            }
            
            // Use the helper method from InventoryBalance model
            $batchesWithDetails = $inventoryBalance->batches_with_details;
            
            // Group by product and sum quantities
            $productTotals = [];
            
            foreach ($batchesWithDetails as $batch) {
                $productName = $batch['product_name'];

                if (!isset($productTotals[$productName])) {
                    $productTotals[$productName] = [
                        'product_name' => $batch['product_name'],
                        'product_batch' => $batch['batch_code'],
                        'expiry_date' => $batch['expiry_date'],
                        'total_quantity' => $batch['quantity']
                    ];
                }
                
            }
            
            // Convert to indexed array
            $result = array_values($productTotals);

            return response()->json([
                'success' => true,
                'message' => 'Driver Inventory Balance retrieved successfully.',
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get inventory balance record: ' . $e->getMessage()
            ], 200);
        }
    }

    
    public function StockRequest(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 200);
        }

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.batch_id' => 'required|integer|min:1|exists:product_batches,id',
            'remarks' => 'nullable|string|max:500'
        ];
        
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'data' => null
            ], 200);
        }

        try {
            // Get items from request
            $items = $request->items;
            
            // Check for duplicate product+batch combinations
            $productBatchPairs = array_map(function($item) {
                return $item['product_id'] . '-' . $item['batch_id'];
            }, $items);
            
            if (count($productBatchPairs) !== count(array_unique($productBatchPairs))) {
                return response()->json([
                    'result' => false,
                    'message' => 'Duplicate product with same batch is not allowed in the same request',
                    'data' => null
                ], 200);
            }

            // Validate each batch exists and has enough quantity
            foreach ($items as $item) {
                $batch = ProductBatch::find($item['batch_id']);
                
                if (!$batch) {
                    return response()->json([
                        'result' => false,
                        'message' => "Batch ID {$item['batch_id']} not found",
                        'data' => null
                    ], 200);
                }
                
                // Check if batch belongs to the product
                if ($batch->product_id != $item['product_id']) {
                    return response()->json([
                        'result' => false,
                        'message' => "Batch does not belong to the selected product",
                        'data' => null
                    ], 200);
                }
                
                // Check if batch has enough quantity
                if ($item['quantity'] > $batch->quantity) {
                    return response()->json([
                        'result' => false,
                        'message' => "Requested quantity for batch {$batch->batch_code} exceeds available stock ({$batch->quantity})",
                        'data' => null
                    ], 200);
                }
                
                // Check if batch is active and not expired
                if ($batch->status !== ProductBatch::STATUS_ACTIVE) {
                    return response()->json([
                        'result' => false,
                        'message' => "Batch {$batch->batch_code} is not active",
                        'data' => null
                    ], 200);
                }
                
                if ($batch->expiry_date <= now()) {
                    return response()->json([
                        'result' => false,
                        'message' => "Batch {$batch->batch_code} has expired",
                        'data' => null
                    ], 200);
                }
            }

            // Get the driver's currently active trip, if any
            $latestTrip = !empty($driver->trip_id)
                ? Trip::where('driver_id', $driver->id)->where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first()
                : null;

            // Format items for storage - store only product_id, quantity, and batch_id
            $itemsForStorage = [];
            foreach ($items as $item) {
                $batch = ProductBatch::find($item['batch_id']);
                
                $itemsForStorage[] = [
                    'product_id' => (int)$item['product_id'],
                    'quantity' => (int)$item['quantity'],
                    'batch_id' => (int)$item['batch_id'],
                ];
            }

            // Create inventory request with items
            $inventoryRequest = InventoryRequest::create([
                'driver_id' => $driver->id,
                'items' => $itemsForStorage, // Store simple array with batch_id
                'status' => InventoryRequest::STATUS_PENDING,
                'trip_id' => $latestTrip->id ?? null,
                'remarks' => $request->remarks ?? null,
            ]);

            // Create notification for admin
            // $notificationService = app(NotificationService::class);
            // $notificationService->createStockRequestNotification($driver, $inventoryRequest);

            // Return simplified response to driver
            $responseData = [
                'id' => $inventoryRequest->id,
                'status' => $inventoryRequest->status,
                'items' => collect($items)->map(function($item) {
                    $product = Product::find($item['product_id']);
                    $batch = ProductBatch::find($item['batch_id']);
                    return [
                        'product_id' => $item['product_id'],
                        'product_name' => $product->name ?? 'Unknown',
                        'batch_id' => $item['batch_id'],
                        'batch_code' => $batch->batch_code ?? 'Unknown',
                        'quantity' => $item['quantity']
                    ];
                }),
                'remarks' => $inventoryRequest->remarks,
                'created_at' => $inventoryRequest->created_at->format('Y-m-d H:i:s')
            ];

            return response()->json([
                'result' => true,
                'message' => 'Inventory request created successfully.',
                'data' => $responseData
            ]);
            
        } catch (\Exception $e) {
            \Log::error('StockRequest API Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => 'Failed to create request: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function getStockRequestRecord(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        
        if (empty($driver->trip_id)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                'data' => null
            ], 200);
        }
        $latestTrip = Trip::where('driver_id', $driver->id)
                        ->where('uuid', $driver->trip_id)
                        ->where('type', Trip::START_TRIP)
                        ->first();
        if (empty($latestTrip)) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                'data' => null
            ], 200);
        }

        try {
            $inventoryRequests = InventoryRequest::where('driver_id', $driver->id)
                ->where('trip_id', $latestTrip->id) // Fixed: was $latestTrip->trip_id, should be $latestTrip->id
                ->orderBy('created_at', 'desc') // Add ordering
                ->get()
                ->map(function ($inventoryRequest) {
                    // Get approver and rejector names
                    $approver = $inventoryRequest->approved_by ? User::find($inventoryRequest->approved_by) : null;
                    $rejector = $inventoryRequest->rejected_by ? User::find($inventoryRequest->rejected_by) : null;
                    
                    // Process items array to add product names and batch information
                    $itemsWithDetails = [];
                    $totalAllocated = 0;
                    
                    if ($inventoryRequest->items && is_array($inventoryRequest->items)) {
                        foreach ($inventoryRequest->items as $item) {
                            $product = Product::find($item['product_id'] ?? null);
                            $batch = null;
                            
                            // Get batch information if batch_id exists
                            if (isset($item['batch_id'])) {
                                $batch = ProductBatch::find($item['batch_id']);
                            }
                            
                            // Calculate allocated quantity (for approved requests)
                            $allocatedQuantity = 0;
                            if ($inventoryRequest->status === 'approved' && isset($item['quantity'])) {
                                $allocatedQuantity = $item['quantity'];
                                $totalAllocated += $allocatedQuantity;
                            }
                            
                            $itemDetail = [
                                'product_id' => $item['product_id'] ?? null,
                                'product_name' => $product ? $product->name : 'Unknown Product',
                                'quantity' => $item['quantity'] ?? 0,
                            ];
                            
                            // Add batch information if available
                            if ($batch) {
                                $itemDetail['batch_id'] = $item['batch_id'];
                                $itemDetail['batch_code'] = $batch->batch_code;
                                $itemDetail['batch_expiry_date'] = $batch->expiry_date ? $batch->expiry_date : null;
                                $itemDetail['batch_status'] = $batch->status;
                            } else {
                                $itemDetail['batch_id'] = null;
                                $itemDetail['batch_code'] = null;
                                $itemDetail['batch_expiry_date'] = null;
                                $itemDetail['batch_status'] = null;
                            }
                            
                            // Add allocation info for approved requests
                            if ($inventoryRequest->status === 'approved') {
                                $itemDetail['allocated_quantity'] = $allocatedQuantity;
                            }
                            
                            $itemsWithDetails[] = $itemDetail;
                        }
                    }
                    
                    // Calculate summary based on status
                    $summary = [
                        'total_items' => count($itemsWithDetails),
                        'total_requested_quantity' => $inventoryRequest->total_quantity,
                    ];
                    
                    // Add allocation summary for approved requests
                    if ($inventoryRequest->status === 'approved') {
                        $summary['total_allocated_quantity'] = $totalAllocated;
                    }
                    
                    // Return formatted data
                    return [
                        'id' => $inventoryRequest->id,
                        'driver_id' => $inventoryRequest->driver_id,
                        'trip_id' => $inventoryRequest->trip_id,
                        'items' => $itemsWithDetails,
                        'summary' => $summary,
                        'status' => $inventoryRequest->status,
                        'status_label' => ucfirst($inventoryRequest->status), // Human-readable status
                        'remarks' => $inventoryRequest->remarks,
                        'rejection_reason' => $inventoryRequest->rejection_reason,
                        'approved_by' => $inventoryRequest->approved_by,
                        'approved_by_name' => $approver ? $approver->name : null,
                        'rejected_by' => $inventoryRequest->rejected_by,
                        'rejected_by_name' => $rejector ? $rejector->name : null,
                        'approved_at' => $inventoryRequest->approved_at ? $inventoryRequest->approved_at->format('Y-m-d H:i:s') : null,
                        'rejected_at' => $inventoryRequest->rejected_at ? $inventoryRequest->rejected_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $inventoryRequest->created_at ? $inventoryRequest->created_at->format('Y-m-d H:i:s') : null,
                        'updated_at' => $inventoryRequest->updated_at ? $inventoryRequest->updated_at->format('Y-m-d H:i:s') : null,
                        'item_count' => $inventoryRequest->item_count,
                        'total_quantity' => $inventoryRequest->total_quantity,
                        'can_be_edited' => $inventoryRequest->status === 'pending', // Helper flag for frontend
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Stock Request Record retrieved successfully.',
                'data' => $inventoryRequests
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('getStockRequestRecord Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock request record: ' . $e->getMessage()
            ], 200);
        }
    }

    public function getStockReturnRecord(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }
        
        try {
            $inventoryReturns = InventoryReturn::where('driver_id', $driver->id)
                ->where('trip_id', $driver->trip_id)
                ->get()
                ->map(function ($inventoryReturn) {
                    // Get approver and rejector names
                    $approver = $inventoryReturn->approved_by ? User::find($inventoryReturn->approved_by) : null;
                    $rejector = $inventoryReturn->rejected_by ? User::find($inventoryReturn->rejected_by) : null;
                    
                    // Process items array to add product names
                    $itemsWithProductNames = [];
                    if ($inventoryReturn->items && is_array($inventoryReturn->items)) {
                        foreach ($inventoryReturn->items as $item) {
                            $product = Product::find($item['product_id'] ?? null);
                            $itemsWithProductNames[] = [
                                'product_id' => $item['product_id'] ?? null,
                                'product_name' => $product ? $product->name : 'Unknown Product',
                                'quantity' => $item['quantity'] ?? 0
                            ];
                        }
                    }
                    
                    // Return formatted data
                    return [
                        'id' => $inventoryReturn->id,
                        'driver_id' => $inventoryReturn->driver_id,
                        'trip_id' => $inventoryReturn->trip_id,
                        'items' => $itemsWithProductNames,
                        'status' => $inventoryReturn->status,
                        'remarks' => $inventoryReturn->remarks,
                        'rejection_reason' => $inventoryReturn->rejection_reason,
                        'approved_by' => $inventoryReturn->approved_by,
                        'approved_by_name' => $approver ? $approver->name : null,
                        'rejected_by' => $inventoryReturn->rejected_by,
                        'rejected_by_name' => $rejector ? $rejector->name : null,
                        'approved_at' => $inventoryReturn->approved_at,
                        'rejected_at' => $inventoryReturn->rejected_at,
                        'created_at' => $inventoryReturn->created_at,
                        'updated_at' => $inventoryReturn->updated_at,
                        'item_count' => $inventoryReturn->item_count,
                        'total_quantity' => $inventoryReturn->total_quantity,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Stock Return Record retrieved successfully.',
                'data' => $inventoryReturns
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock return record: ' . $e->getMessage()
            ], 200);
        }
    }

    public function StockCount(Request $request)
    {
        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        // Get driver's latest trip
        $latestTrip = Trip::where('driver_id', $driver->id)
            ->where('uuid',$driver->trip_id)
            ->where('type', 1)
            ->first();

        if (!$latestTrip) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver start trip record not found.',
                'data' => null
            ], 200);
        }

        // Check if there's already a pending inventory count for this driver
        $existingCount = InventoryCount::where('driver_id', $driver->id)
            ->where('status', '!=',InventoryCount::STATUS_REJECTED)
            ->where('trip_id', $latestTrip->id)
            ->first();

        if($existingCount){
            if($existingCount->status == InventoryCount::STATUS_APPROVED){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'You have completed Stock Out, can proceed to End Trip.',
                    'data' => null
                ], 200);
            } elseif($existingCount->status == InventoryCount::STATUS_PENDING){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'You have requested Stock Count, please contact your Stock Manager for approval.',
                    'data' => null
                ], 200);
            }
        }

        $lorryId = $latestTrip->lorry_id;
        $formattedItems = [];

        try {
            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

            // Check if inventory balance exists and has batches
            if ($inventoryBalance && !empty($inventoryBalance->batches)) {
                // Get all batch IDs from the inventory
                $batchIds = array_keys($inventoryBalance->batches);

                // Fetch batch details with product information
                $batches = ProductBatch::with('product')
                    ->whereIn('id', $batchIds)
                    ->where('status', ProductBatch::STATUS_ACTIVE)
                    ->get();

                // Group by product
                $productGroups = [];
                
                foreach ($batches as $batch) {
                    $productId = $batch->product_id;
                    $availableQty = $inventoryBalance->batches[$batch->id] ?? 0;
                    
                    // Skip if no quantity available (but still include if quantity is 0? No, skip)
                    if ($availableQty <= 0) {
                        continue;
                    }
                    
                    // Initialize product group if not exists
                    if (!isset($productGroups[$productId])) {
                        $productGroups[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $batch->product->name,
                            'product_code' => $batch->product->code,
                            'unit_code' => $batch->product->unit_code,
                            'current_quantity' => 0,
                            'batches' => [],
                        ];
                    }
                    
                    // Add batch details
                    $productGroups[$productId]['batches'][] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'current_quantity' => $availableQty,
                        'counted_quantity' => $availableQty, // Default to current quantity
                        'warehouse_id' => null, // Will be set by admin
                        'expiry_date' => $batch->expiry_date,
                        'formatted_expiry_date' => $batch->formatted_expiry_date,
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                    ];
                    
                    // Add to total product quantity
                    $productGroups[$productId]['current_quantity'] += $availableQty;
                }

                // Convert product groups to array
                $formattedItems = array_values($productGroups);
            }

            // If no inventory items found (empty inventory)
            if (empty($formattedItems)) {
                // Create an empty stock count record with null items
                // This allows the driver to submit a stock count even with no inventory
                $inventoryCount = InventoryCount::create([
                    'driver_id' => $driver->id,
                    'trip_id' => $latestTrip->id,
                    'items' => null, // No items to count
                    'lorry_id' => $lorryId,
                    'status' => InventoryCount::STATUS_PENDING,
                    'remarks' => 'Stock count request - No inventory found in lorry',
                ]);

                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'Stock count request created successfully. No inventory found in your lorry.',
                    'data' => [
                        'count_id' => $inventoryCount->id,
                        'status' => $inventoryCount->status,
                        'total_products' => 0,
                        'total_batches' => 0,
                        'items' => [], // Empty items array
                        'has_inventory' => false,
                        'message' => 'Your lorry currently has no inventory. You can still submit this stock count.'
                    ]
                ], 200);
            }

            // Create inventory count record with items
            $inventoryCount = InventoryCount::create([
                'driver_id' => $driver->id,
                'trip_id' => $latestTrip->id,
                'items' => $formattedItems,
                'lorry_id' => $lorryId,
                'status' => InventoryCount::STATUS_PENDING,
                'remarks' => 'Auto-generated stock count request from driver app',
            ]);

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Stock count request created successfully.',
                'data' => [
                    'count_id' => $inventoryCount->id,
                    'status' => $inventoryCount->status,
                    'total_products' => count($formattedItems),
                    'total_batches' => collect($formattedItems)->sum(function($item) {
                        return count($item['batches']);
                    }),
                    'has_inventory' => true,
                    'items' => $formattedItems
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('StockCount API Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Failed to create stock count request: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }
    
    public function StockCountStatus(Request $request)
    {

        // Validate session
        $driver = Driver::where('session', $request->header('session'))->first();
        if(empty($driver)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
 			$trip = Trip::where('driver_id', $driver->id)
                    ->where('uuid',$driver->trip_id)
                    ->where('type', 1)
                    ->first();

            if (empty($trip)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 200);
            }

            $inventoryCount = InventoryCount::where('driver_id', $driver->id)->where('trip_id',$trip->id)->where('status', '!=', InventoryCount::STATUS_REJECTED)->first();
                        
            if($inventoryCount){
                if($inventoryCount->status == InventoryCount::STATUS_APPROVED ){
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__ . $this->message_separator . 'Stock Count Completed',
                        'data' => [
                            'isDone' => true
                        ]
                    ], 200);
                }elseif($inventoryCount->status == InventoryCount::STATUS_PENDING ){
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__ . $this->message_separator . 'Stock Count is in Pending',
                        'data' => [
                            'isDone' => false
                        ]
                    ], 200);
                
                }else{
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__ . $this->message_separator . 'Stock Count Not Complete yet.',
                        'data' => [
                            'isDone' => false
                        ]
                    ], 200);
                }
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'Stock Count Record Not found.',
                    'data' => [
                        'isDone' => false
                    ]
                ], 200);
            }
            

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock count status: ' . $e->getMessage()
            ], 500);
        }
    }






    //Manager mobile side API

    public function managerLogin(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'employeeid' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $user = User::where('username', $data['employeeid'])->first();

            if (empty($user) || !$user) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'User not found.',
                    'data' => null
                ], 200);
            }
          
            if (Hash::check($data['password'], $user->password)) {
                
                $session = $user->session;
                $user->session = session_create_id();
                $user->save();

                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.login_successfully',
                    'data' => [
                        'manager' => $user,
                    ]
                ], 200);

            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_credential',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function managerLogout(Request $request){
        try{
            //validation
            $validator = Validator::make($request->all(), [
                'session' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null
                ], 400);
            }
            //process
            $data = $request->all();
            $user = User::where('session', $data['session'])->first();
            if(!empty($user)){
                $user->session = NULL;
                $user->save();
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.logout_successfully',
                    'data' => null
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
        }
        catch(Exception $e){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getDriverProduct(Request $request, $id =null)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Get all inventory balances that have batches and are associated with active lorries
            // First, get all active lorries (status = 0 / in use)
            if($id){
                $lorry = Lorry::find($id);
                // Check if lorry exists
                if (!$lorry) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Van not found',
                        'data' => null
                    ], 404);
                }
                
                // Check if lorry is active (status = 0 based on your logic)
                if ($lorry->status != 0) {
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__ . $this->message_separator . 'This van is not in use.',
                        'data' => []
                    ], 200);
                }
                
                $activeLorries = [$lorry->id];
            }else{
                $activeLorries = Lorry::where('status', 0) // Adjust status value as needed
                    ->pluck('id')
                    ->toArray();
            }
            
            if (empty($activeLorries)) {
                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'No active van found',
                    'data' => []
                ], 200);
            }

            // Get inventory balances only for active lorries
            $inventoryBalances = InventoryBalance::whereIn('lorry_id', $activeLorries)
                ->whereNotNull('batches')
                ->where('batches', '!=', '[]')
                ->where('batches', '!=', '{}')
                ->get();

            if ($inventoryBalances->isEmpty()) {
                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'No active van with inventory found',
                    'data' => []
                ], 200);
            }

            $activeLorriesWithInventory = [];

            foreach ($inventoryBalances as $inventoryBalance) {
                $lorryId = $inventoryBalance->lorry_id;
                
                // Get the lorry details
                $lorry = Lorry::find($lorryId);
                if (!$lorry) {
                    continue;
                }
                // Get the driver assigned to this lorry
                $driver = $lorry->driver_id ? Driver::find($lorry->driver_id) : null;

                // Get all batch IDs from the inventory
                $batchIds = array_keys($inventoryBalance->batches);

                // Fetch batch details with product information
                $batches = ProductBatch::with('product')
                    ->whereIn('id', $batchIds)
                    ->where('quantity', '>', 0)
                    ->where('status', ProductBatch::STATUS_ACTIVE)
                    ->orderBy('expiry_date', 'asc')
                    ->get();

                if ($batches->isEmpty()) {
                    continue;
                }

                // Group batches by product
                $productsWithBatches = [];
                
                foreach ($batches as $batch) {
                    $productId = $batch->product_id;
                    $availableQty = $inventoryBalance->batches[$batch->id] ?? 0;
                    
                    // Skip if no quantity available
                    if ($availableQty <= 0) {
                        continue;
                    }
                    
                    // Get product info
                    $product = $batch->product;
                    if (!$product) {
                        continue;
                    }
                    
                    // Initialize product entry if not exists
                    if (!isset($productsWithBatches[$productId])) {
                        $productsWithBatches[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $product->name,
                            'product_code' => $product->unit_code,
                            'price' => $product->price,
                            'unit_code' => $product->unit_code,
                            'total_quantity' => 0,
                            'batches' => []
                        ];
                    }
                    
                    // Add batch details
                    $productsWithBatches[$productId]['batches'][] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $availableQty,
                        'expiry_date' => $batch->expiry_date,
                        'formatted_expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                        'status' => $batch->status_text,
                        'is_active' => $batch->isActive()
                    ];
                    
                    // Add to total quantity
                    $productsWithBatches[$productId]['total_quantity'] += $availableQty;
                }

                if (empty($productsWithBatches)) {
                    continue;
                }

                // Convert to array (reset keys)
                $productsList = array_values($productsWithBatches);

                // Sort products by name
                usort($productsList, function($a, $b) {
                    return strcasecmp($a['product_name'], $b['product_name']);
                });

                // Sort batches within each product by expiry date
                foreach ($productsList as &$product) {
                    usort($product['batches'], function($a, $b) {
                        return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
                    });
                }
                unset($product);

                // Get latest trip info for display purposes (optional)
                $latestTrip = Trip::where('lorry_id', $lorryId)
                    ->orderBy('date', 'desc')
                    ->first();

                $activeLorriesWithInventory[] = [
                    'lorry_id' => $lorryId,
                    'lorry_number' => $lorry->lorryno,
                    'lorry_status' => $lorry->status,
                    'driver_name' => $driver ? $driver->name : null,
                    'driver_code' => $driver->employeeid ?? null,
                    'trip_info' => $latestTrip ? [
                        'trip_id' => $latestTrip->id,
                        'trip_date' => $latestTrip->date,
                        'trip_status' => $latestTrip->status ?? null
                    ] : null,
                    'products' => $productsList,
                    'summary' => [
                        'total_products' => count($productsList),
                        'total_batches' => $batches->count(),
                        'total_quantity' => array_sum(array_column($productsList, 'total_quantity'))
                    ]
                ];
            }

            // Sort by driver name
            usort($activeLorriesWithInventory, function($a, $b) {
                return strcasecmp($a['driver_name'] ?? '', $b['driver_name'] ?? '');
            });

            // Calculate overall summary
            $overallSummary = [
                'total_active_lorries_with_inventory' => count($activeLorriesWithInventory),
                'total_products' => array_sum(array_column($activeLorriesWithInventory, 'summary.total_products')),
                'total_batches' => array_sum(array_column($activeLorriesWithInventory, 'summary.total_batches')),
                'total_quantity' => array_sum(array_column($activeLorriesWithInventory, 'summary.total_quantity'))
            ];

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Driver inventory retrieved successfully',
                'data' => [
                    'drivers' => $activeLorriesWithInventory,
                    'overall_summary' => $overallSummary
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error in getDriverProduct: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error getting driver inventory: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getStockOutWarehouseInventory(Request $request)
    {
        // Validate session or authentication
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            $warehouses = Warehouse::where('stock_out_enabled',1)
                    ->orderBy('name')
                    ->get();

            $responseData = [
                'warehouses' => $warehouses,
            ];
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Warehouse for Stock Out retrieved successfully',
                'data' => $responseData
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error in getting warehouse for stockout: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error getting warehouse for stockout: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    public function getWarehouseInventory(Request $request, $id =null)
    {
        // Validate session or authentication
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            $warehouseId = $id;
            
            // If warehouse_id is provided, get specific warehouse
            if ($warehouseId) {
                $warehouse = Warehouse::where('id', $warehouseId)
                    ->where('status', Warehouse::STATUS_ACTIVE)
                    ->first();
                    
                if (!$warehouse) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Warehouse not found.',
                        'data' => null
                    ], 404);
                }
                
                $warehouses = collect([$warehouse]);
            } else {
                // Get all active warehouses
                $warehouses = Warehouse::orderBy('name')
                    ->get();
                    
                if ($warehouses->isEmpty()) {
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__ . $this->message_separator . 'No active warehouses found',
                        'data' => []
                    ], 200);
                }
            }

            $warehouseData = [];
            
            foreach ($warehouses as $warehouse) {
                // Get inventory balances for this warehouse with batch details
                $inventoryBalances = WarehouseInventoryBalance::with(['product', 'batch'])
                    ->where('warehouse_id', $warehouse->id)
                    ->where('quantity', '>', 0) // Only show items with stock
                    ->orderBy('quantity', 'desc')
                    ->get();
                
                if ($inventoryBalances->isEmpty()) {
                    $warehouseData[] = [
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'warehouse_location' => $warehouse->location,
                        'status' => $warehouse->status,
                        'has_inventory' => false,
                        'products' => [],
                        'summary' => [
                            'total_products' => 0,
                            'total_batches' => 0,
                            'total_quantity' => 0
                        ]
                    ];
                    continue;
                }
                
                // Group by product
                $productsByBatch = [];
                $totalQuantity = 0;
                
                foreach ($inventoryBalances as $balance) {
                    $product = $balance->product;
                    $batch = $balance->batch;
                    
                    if (!$product || !$batch) {
                        continue;
                    }
                    
                    $productId = $product->id;
                    
                    // Initialize product entry if not exists
                    if (!isset($productsByBatch[$productId])) {
                        $productsByBatch[$productId] = [
                            'product_id' => $productId,
                            'product_name' => $product->name,
                            'product_code' => $product->unit_code,
                            'price' => $product->price,
                            'unit_code' => $product->unit_code,
                            'total_quantity' => 0,
                            'batches' => []
                        ];
                    }
                    
                    // Add batch details
                    $batchQuantity = $balance->quantity;
                    $totalQuantity += $batchQuantity;
                    
                    $productsByBatch[$productId]['batches'][] = [
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $batchQuantity,
                        'expiry_date' => $batch->expiry_date,
                        'formatted_expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                        'batch_status' => $batch->status_text,
                        'is_active' => $batch->isActive(),
                        'product_id' => $productId,
                        'product_name' => $product->name
                    ];
                    
                    // Add to total quantity for this product
                    $productsByBatch[$productId]['total_quantity'] += $batchQuantity;
                }
                
                // Convert to array and sort by product name
                $productsList = array_values($productsByBatch);
                usort($productsList, function($a, $b) {
                    return strcasecmp($a['product_name'], $b['product_name']);
                });
                
                // Sort batches within each product by expiry date (FEFO)
                foreach ($productsList as &$product) {
                    usort($product['batches'], function($a, $b) {
                        return strtotime($a['expiry_date']) - strtotime($b['expiry_date']);
                    });
                }
                unset($product);
                
                // Calculate summary
                $totalBatches = $inventoryBalances->count();
                $totalProducts = count($productsList);
                
                $warehouseData[] = [
                    'warehouse_id' => $warehouse->id,
                    'warehouse_name' => $warehouse->name,
                    'warehouse_location' => $warehouse->location,
                    'status' => $warehouse->status,
                    'has_inventory' => true,
                    'products' => $productsList,
                    'summary' => [
                        'total_products' => $totalProducts,
                        'total_batches' => $totalBatches,
                        'total_quantity' => $totalQuantity
                    ]
                ];
            }
            
            // Calculate overall summary if multiple warehouses
            $overallSummary = null;
            if (count($warehouseData) > 1) {
                $overallSummary = [
                    'total_warehouses' => count($warehouseData),
                    'total_warehouses_with_stock' => collect($warehouseData)->filter(function($w) {
                        return $w['has_inventory'];
                    })->count(),
                    'total_products' => collect($warehouseData)->sum(function($w) {
                        return $w['summary']['total_products'];
                    }),
                    'total_batches' => collect($warehouseData)->sum(function($w) {
                        return $w['summary']['total_batches'];
                    }),
                    'total_quantity' => collect($warehouseData)->sum(function($w) {
                        return $w['summary']['total_quantity'];
                    })
                ];
            }
            
            $responseData = [
                'warehouses' => $warehouseData,
                'overall_summary' => $overallSummary
            ];
            
            // If only one warehouse was requested, return simplified structure
            if ($warehouseId && count($warehouseData) == 1) {
                $responseData = $warehouseData[0];
            }
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Warehouse inventory retrieved successfully',
                'data' => $responseData
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('Error in getWarehouseInventory: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error getting warehouse inventory: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getStockCount(Request $request, $id = null)
    {
        try {
            // Validate session
            $user = User::where('session', $request->header('session'))->first();
            if(empty($user)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                    'data' => null
                ], 401);
            }
            
            // Build query
            $query = InventoryCount::query();
            
            // If ID is provided, filter by ID
            if ($id !== null) {
                $query->where('id', $id);
            }
            
            $inventoryCounts = $query->get();
            
            // Check if record exists when ID is provided
            if ($id !== null && $inventoryCounts->isEmpty()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Stock count record not found',
                    'data' => null
                ], 404);
            }
            
            // Get all unique product IDs from all inventory counts (only if items exist)
            $allProductIds = [];
            foreach ($inventoryCounts as $count) {
                $items = $count->items;
                if (!empty($items) && is_array($items)) {
                    foreach ($items as $item) {
                        if (isset($item['product_id'])) {
                            $allProductIds[] = $item['product_id'];
                        }
                    }
                }
            }
            
            // Fetch all products in one query (only if there are product IDs)
            $products = collect();
            if (!empty($allProductIds)) {
                $allProductIds = array_unique($allProductIds);
                $products = Product::whereIn('id', $allProductIds)
                    ->get()
                    ->keyBy('id');
            }
            
            // Fetch all drivers in one query (for multiple records)
            $driverIds = $inventoryCounts->pluck('driver_id')->filter()->unique()->toArray();
            $drivers = Driver::whereIn('id', $driverIds)
                ->get()
                ->keyBy('id');
            
            // Fetch all warehouses for reference
            $warehouseIds = [];
            foreach ($inventoryCounts as $count) {
                $items = $count->items;
                if (!empty($items) && is_array($items)) {
                    foreach ($items as $item) {
                        if (isset($item['batches']) && is_array($item['batches'])) {
                            foreach ($item['batches'] as $batch) {
                                if (isset($batch['warehouse_id'])) {
                                    $warehouseIds[] = $batch['warehouse_id'];
                                }
                            }
                        }
                    }
                }
            }
            
            $warehouseIds = array_unique($warehouseIds);
            $warehouses = Warehouse::whereIn('id', $warehouseIds)
                ->get()
                ->keyBy('id');
            
            // Format the response
            $formattedCounts = $inventoryCounts->map(function ($count) use ($products, $drivers, $warehouses) {
                $items = $count->items;
                $formattedItems = [];
                
                // Check if items exist and is an array
                if (!empty($items) && is_array($items)) {
                    $formattedItems = array_map(function ($item) use ($products, $warehouses) {
                        $productId = $item['product_id'];
                        $product = $products[$productId] ?? null;
                        
                        // Handle batches
                        $formattedBatches = [];
                        if (isset($item['batches']) && is_array($item['batches'])) {
                            foreach ($item['batches'] as $batch) {
                                $warehouseId = $batch['warehouse_id'] ?? null;
                                $warehouse = $warehouseId ? ($warehouses[$warehouseId] ?? null) : null;
                                
                                $formattedBatches[] = [
                                    'batch_id' => $batch['batch_id'] ?? null,
                                    'batch_code' => $batch['batch_code'] ?? null,
                                    'current_quantity' => $batch['current_quantity'] ?? 0,
                                    'counted_quantity' => $batch['counted_quantity'] ?? null,
                                    'warehouse_id' => $warehouseId,
                                    'warehouse_name' => $warehouse ? $warehouse->name : null,
                                    'expiry_date' => $batch['expiry_date'] ?? null,
                                    'formatted_expiry_date' => $batch['formatted_expiry_date'] ?? null,
                                    'is_expiring_soon' => $batch['is_expiring_soon'] ?? false
                                ];
                            }
                        }
                        
                        // If no batches, create a default structure
                        if (empty($formattedBatches)) {
                            $formattedBatches[] = [
                                'batch_id' => null,
                                'batch_code' => 'N/A',
                                'current_quantity' => $item['current_quantity'] ?? 0,
                                'counted_quantity' => $item['counted_quantity'] ?? null,
                                'warehouse_id' => $item['warehouse_id'] ?? null,
                                'warehouse_name' => null,
                                'expiry_date' => null,
                                'formatted_expiry_date' => null,
                                'is_expiring_soon' => false
                            ];
                        }
                        
                        return [
                            'product_id' => $item['product_id'],
                            'product_name' => $product ? $product->name : ($item['product_name'] ?? 'Unknown Product'),
                            'product_code' => $product ? $product->code : null,
                            'unit_code' => $product ? $product->unit_code : null,
                            'current_quantity' => $item['current_quantity'] ?? 0,
                            'counted_quantity' => $item['counted_quantity'] ?? null,
                            'batches' => $formattedBatches,
                            'has_batches' => count($formattedBatches) > 0
                        ];
                    }, $items);
                }
                
                // Get driver info from pre-fetched collection
                $driver = null;
                if ($count->driver_id) {
                    $driver = $drivers[$count->driver_id] ?? null;
                }
                
                // Get approver and rejector names
                $approverName = null;
                if ($count->approved_by) {
                    $approver = \App\Models\User::find($count->approved_by);
                    $approverName = $approver ? $approver->name : null;
                }
                
                $rejectorName = null;
                if ($count->rejected_by) {
                    $rejector = \App\Models\User::find($count->rejected_by);
                    $rejectorName = $rejector ? $rejector->name : null;
                }
                
                return [
                    'id' => $count->id,
                    'driver_id' => $count->driver_id,
                    'driver_name' => $driver ? $driver->name : null,
                    'lorry_id' => $count->lorry_id,
                    'lorry_number' => $count->lorry ? $count->lorry->lorryno : null,
                    'trip_id' => $count->trip_id,
                    'items' => $formattedItems,
                    'has_items' => !empty($formattedItems),
                    'total_products' => count($formattedItems),
                    'status' => $count->status,
                    'remarks' => $count->remarks,
                    'rejection_reason' => $count->rejection_reason,
                    'approved_by' => $approverName,
                    'approved_by_id' => $count->approved_by,
                    'rejected_by' => $rejectorName,
                    'rejected_by_id' => $count->rejected_by,
                    'approved_at' => $count->approved_at ? $count->approved_at->toDateTimeString() : null,
                    'rejected_at' => $count->rejected_at ? $count->rejected_at->toDateTimeString() : null,
                    'created_at' => $count->created_at ? $count->created_at->toDateTimeString() : null,
                    'updated_at' => $count->updated_at ? $count->updated_at->toDateTimeString() : null
                ];
            });

            // Return single object if ID was provided, otherwise return array
            $responseData = ($id !== null) ? $formattedCounts->first() : $formattedCounts;
            
            // Add summary information for single record
            if ($id !== null && $responseData) {
                $totalCurrentQty = 0;
                $totalCountedQty = 0;
                
                if (!empty($responseData['items'])) {
                    foreach ($responseData['items'] as $item) {
                        if (isset($item['batches'])) {
                            foreach ($item['batches'] as $batch) {
                                $totalCurrentQty += $batch['current_quantity'] ?? 0;
                                $totalCountedQty += $batch['counted_quantity'] ?? 0;
                            }
                        }
                    }
                }
                
                $responseData['summary'] = [
                    'total_current_quantity' => $totalCurrentQty,
                    'total_counted_quantity' => $totalCountedQty,
                    'total_difference' => $totalCountedQty - $totalCurrentQty,
                    'has_inventory' => $totalCurrentQty > 0,
                    'is_empty_inventory' => $totalCurrentQty === 0 && empty($responseData['items'])
                ];
            }
            
            $message = ($id !== null) 
                ? 'Stock count record retrieved successfully' 
                : 'Stock count records retrieved successfully';
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $message,
                'data' => $responseData,
                'total_records' => ($id !== null) ? 1 : $formattedCounts->count()
            ], 200);
            
        } catch (Exception $e) {
            \Log::error('Error in getStockCount: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    
    public function approveStockCount(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        // Make items optional for approval (can be empty)
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:inventory_counts,id',
            'items' => 'nullable|array', // Changed from required to nullable
            'items.*.product_id' => 'required_with:items|integer|exists:products,id',
            'items.*.batches' => 'required_with:items|array',
            'items.*.batches.*.batch_id' => 'required_with:items|exists:product_batches,id',
            'items.*.batches.*.counted_quantity' => 'required_with:items|numeric|min:0',
            'items.*.batches.*.current_quantity' => 'required_with:items|numeric|min:0',
            'items.*.batches.*.warehouse_id' => 'required_with:items|exists:warehouses,id',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 200);
        }
        
        $data = $request->all();
        $id = $data['id'];

        $inventoryCount = InventoryCount::with(['driver', 'lorry'])->find($id);

        if (!$inventoryCount) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Stock count not found',
                'data' => null
            ], 200);
        }
        
        if (!$inventoryCount->canBeApproved()) {
            return response()->json([
                'result' => false,
                'message' => '' . __LINE__ . $this->message_separator . 'This stock count request cannot be approved, the status is not in pending.',
                'data' => null
            ], 200);
        }

        try {
            // Check if there are items to process
            $hasItems = isset($data['items']) && is_array($data['items']) && count($data['items']) > 0;
            
            // If no items provided, check if the existing record has items
            $existingItems = $inventoryCount->items;
            $hasExistingItems = !empty($existingItems) && is_array($existingItems) && count($existingItems) > 0;
            
            $missingCountedItems = [];
            $missingWarehouseItems = [];
            $processedBatches = [];
            
            // Only process items if they exist
            if ($hasItems) {
                foreach ($data['items'] as $item) {
                    if (isset($item['batches']) && is_array($item['batches'])) {
                        foreach ($item['batches'] as $batch) {
                            $productName = $item['product_name'] ?? 'Unknown Product';
                            $batchCode = $batch['batch_code'] ?? 'Unknown Batch';
                            $countedQty = $batch['counted_quantity'] ?? null;
                            $warehouseId = $batch['warehouse_id'] ?? null;
                            
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
                                    'product_name' => $productName,
                                    'batch_code' => $batchCode,
                                    'counted_quantity' => $countedQty,
                                    'warehouse_id' => $warehouseId,
                                    'current_quantity' => $batch['current_quantity'] ?? 0
                                ];
                            }
                        }
                    }
                }
            } elseif ($hasExistingItems) {
                // If no items in request but existing record has items, process existing items
                foreach ($existingItems as $item) {
                    if (isset($item['batches']) && is_array($item['batches'])) {
                        foreach ($item['batches'] as $batch) {
                            $productName = $item['product_name'] ?? 'Unknown Product';
                            $batchCode = $batch['batch_code'] ?? 'Unknown Batch';
                            $countedQty = $batch['counted_quantity'] ?? null;
                            $warehouseId = $batch['warehouse_id'] ?? null;
                            
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
                                    'product_name' => $productName,
                                    'batch_code' => $batchCode,
                                    'counted_quantity' => $countedQty,
                                    'warehouse_id' => $warehouseId,
                                    'current_quantity' => $batch['current_quantity'] ?? 0
                                ];
                            }
                        }
                    }
                }
            }
            
            // If there are items missing counted_quantity, return error (only if there were items to process)
            if (!empty($missingCountedItems)) {
                $errorMessage = 'Cannot approve. Please fill in counted quantity for: ' . implode(', ', array_slice($missingCountedItems, 0, 5));
                if (count($missingCountedItems) > 5) {
                    $errorMessage .= ' and ' . (count($missingCountedItems) - 5) . ' more';
                }
                
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . $errorMessage,
                    'data' => null
                ], 200);
            }
            
            // If there are items missing warehouse, return error (only if there were items to process)
            if (!empty($missingWarehouseItems)) {
                $errorMessage = 'Cannot approve. Please select return warehouse for: ' . implode(', ', array_slice($missingWarehouseItems, 0, 5));
                if (count($missingWarehouseItems) > 5) {
                    $errorMessage .= ' and ' . (count($missingWarehouseItems) - 5) . ' more';
                }
                
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . $errorMessage,
                    'data' => null
                ], 200);
            }
            
            // Begin transaction
            \DB::beginTransaction();
            
            try {
                // Process each batch to return stock to warehouse (only if there are batches)
                if (!empty($processedBatches)) {
                    foreach ($processedBatches as $batchData) {
                        // Find the product batch
                        $productBatch = ProductBatch::find($batchData['batch_id']);
                        
                        if (!$productBatch) {
                            throw new \Exception('Product batch not found: ' . $batchData['batch_code']);
                        }
                        
                        // Update batch quantity (add back to batch)
                        $productBatch->quantity += $batchData['counted_quantity'];
                        $productBatch->save();
                        
                        // Create inventory transaction for stock return to warehouse
                        InventoryTransaction::create([
                            'product_id' => $batchData['product_id'],
                            'batch_id' => $batchData['batch_id'],
                            'warehouse_id' => $batchData['warehouse_id'],
                            'quantity' => $batchData['counted_quantity'],
                            'type' => InventoryTransaction::TYPE_STOCK_IN,
                            'date' => now(),
                            'user' => $user->name,
                            'remark' => 'Stock return from Stock Out -[' . ($inventoryCount->driver->name ?? 'Unknown') . '] - Batch: [' . $batchData['batch_code'] . ']',
                        ]);
                        
                        // Update warehouse inventory balance
                        $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $batchData['warehouse_id'])
                            ->where('batch_id', $batchData['batch_id'])
                            ->first();
                        
                        if ($warehouseInventory) {
                            $warehouseInventory->increaseQuantity($batchData['counted_quantity']);
                        } else {
                            // Create new warehouse inventory balance if not exists
                            WarehouseInventoryBalance::create([
                                'warehouse_id' => $batchData['warehouse_id'],
                                'batch_id' => $batchData['batch_id'],
                                'product_id' => $batchData['product_id'],
                                'quantity' => $batchData['counted_quantity']
                            ]);
                        }
                    }
                }
                
                // Update inventory count status
                $updateData = [
                    'status' => InventoryCount::STATUS_APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'remarks' => $data['remarks'] ?? $inventoryCount->remarks
                ];
                
                // Only update items if provided in request
                if ($hasItems) {
                    $updateData['items'] = $data['items'];
                }
                
                $inventoryCount->update($updateData);
                
                // Remove driver's inventory balance (lorry inventory)
                $inventoryBalance = InventoryBalance::where('lorry_id', $inventoryCount->lorry_id)->first();
                if ($inventoryBalance) {
                    $inventoryBalance->delete();
                }

                // Approval is what ends the trip now - see endDriverTrip()
                $inventoryCount->endDriverTrip();

                \DB::commit();
                
                // Load relationships for response
                $inventoryCount->load(['driver', 'lorry', 'approver']);
                
                $message = !empty($processedBatches) 
                    ? 'Stock Count approved successfully. ' . count($processedBatches) . ' batches processed.'
                    : 'Stock Count approved successfully. No inventory to return.';
                
                return response()->json([
                    'result' => true,
                    'message' => '' . __LINE__ . $this->message_separator . $message,
                    'data' => [
                        'id' => $inventoryCount->id,
                        'driver_id' => $inventoryCount->driver_id,
                        'driver_name' => $inventoryCount->driver->name ?? null,
                        'lorry_id' => $inventoryCount->lorry_id,
                        'lorry_number' => $inventoryCount->lorry->lorryno ?? null,
                        'status' => $inventoryCount->status,
                        'approved_by' => $inventoryCount->approver->name ?? null,
                        'approved_at' => $inventoryCount->approved_at,
                        'remarks' => $inventoryCount->remarks,
                        'processed_batches' => count($processedBatches),
                        'total_quantity_returned' => array_sum(array_column($processedBatches, 'counted_quantity')),
                        'has_inventory' => !empty($processedBatches)
                    ]
                ], 200);
                
            } catch (\Exception $e) {
                \DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error in approveStockCount: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => '' . __LINE__ . $this->message_separator . 'Stock Count Failed approved: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }


    /**
     * Stock In API - Distribute batch from warehouse to multiple lorries
     */
    public function Stockin(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_batch_id' => 'required|exists:product_batches,id',
            'lorry_ids' => 'required|array',
            'lorry_ids.*' => 'exists:lorrys,id',
            'quantity_per_lorry' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Check if selected lorries are in use (status = 0)
        $activeLorries = Lorry::whereIn('id', $request->lorry_ids)
            ->where('status', 1) // 0 = in use/active
            ->pluck('lorryno', 'id')
            ->toArray();

        if (!empty($activeLorries)) {
            $activeLorryDetails = [];
            foreach ($activeLorries as $id => $lorryno) {
                $activeLorryDetails[] = $lorryno ;
            }
            $activeLorryNumbers = implode(', ', $activeLorryDetails);
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . "Cannot stock in to vans that are currently not in use: {$activeLorryNumbers}.",
                'data' => null
            ], 200);
        }
        
        $productBatch = ProductBatch::find($request->product_batch_id);
        $warehouseId = $request->warehouse_id;
        
        // Check if warehouse has this batch
        $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $warehouseId)
            ->where('batch_id', $productBatch->id)
            ->first();

        if (!$warehouseInventory) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'This batch is not available in the selected warehouse.',
                'data' => null
            ], 200);
        }

        // Calculate total needed
        $totalNeeded = $request->quantity_per_lorry * count($request->lorry_ids);

        if ($warehouseInventory->quantity < $totalNeeded) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Insufficient batch quantity in warehouse. Available: ' . $warehouseInventory->quantity . ', Needed: ' . $totalNeeded,
                'data' => null
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            // Remove from warehouse
            $warehouseInventory->decreaseQuantity($totalNeeded);

            $processedLorries = [];
            
            foreach ($request->lorry_ids as $lorryId) {
                $lorry = Lorry::find($lorryId);
                
                // Get or create inventory balance for this lorry
                $inventoryBalance = InventoryBalance::firstOrCreate(
                    ['lorry_id' => $lorryId],
                    ['batches' => []]
                );

                // Add batch to lorry's inventory
                $inventoryBalance->updateBatchQuantity(
                    $productBatch->id, 
                    $request->quantity_per_lorry, 
                    'add'
                );
                
                // Create inventory transaction for lorry (stock in)
                InventoryTransaction::create([
                    'type' => InventoryTransaction::TYPE_STOCK_IN,
                    'lorry_id' => $lorryId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => $request->quantity_per_lorry,
                    'date' => now(),
                    'user' => $user->name,
                    'remark' => 'Received from warehouse'
                ]);

                // Create inventory transaction for warehouse (stock out)
                InventoryTransaction::create([
                    'type' => InventoryTransaction::TYPE_STOCK_OUT,
                    'warehouse_id' => $warehouseId,
                    'lorry_id' => $lorryId,
                    'product_id' => $productBatch->product_id,
                    'batch_id' => $productBatch->id,
                    'quantity' => -$request->quantity_per_lorry,
                    'date' => now(),
                    'user' => $user->name,
                    'remark' => "Distributed to van #{$lorry->lorryno}."
                ]);
                
                $processedLorries[] = [
                    'lorry_id' => $lorryId,
                    'lorry_number' => $lorry->lorryno,
                    'quantity_received' => $request->quantity_per_lorry
                ];
            }
            
            // Update batch status if quantity becomes 0
            $productBatch->refresh();
            if ($productBatch->quantity <= 0) {
                $productBatch->status = 2; // Inactive
                $productBatch->save();
            }
            
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Successfully distributed ' . $totalNeeded . ' units to ' . count($request->lorry_ids) . ' van(s).',
                'data' => [
                    'total_units_distributed' => $totalNeeded,
                    'total_lorries' => count($request->lorry_ids),
                    'quantity_per_lorry' => $request->quantity_per_lorry,
                    'batch' => [
                        'id' => $productBatch->id,
                        'batch_code' => $productBatch->batch_code,
                        'product_id' => $productBatch->product_id,
                        'remaining_quantity' => $productBatch->quantity
                    ],
                    'warehouse' => [
                        'id' => $warehouseId,
                        'remaining_quantity' => $warehouseInventory->fresh()->quantity
                    ],
                    'lorries' => $processedLorries
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Stock In Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error processing stock in: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    /**
     * Get batches for a specific warehouse (AJAX)
     */
    public function apiGetWarehouseBatches(Request $request, $id)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        if (!$id) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator .'Warehouse Id is required',
                'data' => null
            ], 200);
        }
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator .'Warehouse not found.',
                'data' => null
            ], 200);
        }
        try {
            
            $inventory = WarehouseInventoryBalance::with(['product', 'batch'])
                ->where('warehouse_id', $id)
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
                        'expiry_date' => $item->batch ? $item->batch->expiry_date : null,
                        'formatted_expiry_date' => $item->batch ? $item->batch->formatted_expiry_date : 'N/A',
                        'is_expiring_soon' => $item->batch ? $item->batch->isExpiringSoon() : false,
                        'days_to_expiry' => $item->batch ? $item->batch->days_to_expiry : null,
                    ];
                });

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Warehouse batches retrieved successfully',
                'data' => [
                    'warehouse_id' => $id,
                    'warehouse_name' => $warehouse->name,
                    'warehouse_location' => $warehouse->location ?? null,
                    'total_batches' => $inventory->count(),
                    'total_quantity' => $inventory->sum('quantity'),
                    'inventory' => $inventory
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Get Warehouse Batches Error: ' . $e->getMessage());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error loading warehouse batches: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    /**
     * Stock Out API - Return batch from lorry to warehouse
     */
    public function Stockout(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'lorry_id' => 'required|exists:lorrys,id',
            'batch_id' => 'required|exists:product_batches,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Check if lorry is active (status = 0) before allowing stock out
        $lorry = Lorry::find($request->lorry_id);
        if ($lorry->status != 0) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Cannot process stock out. This van is not currently in use.',
                'data' => null
            ], 200);
        }

        $inventoryBalance = InventoryBalance::where('lorry_id', $request->lorry_id)->first();

        if (!$inventoryBalance) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'No inventory found for this van',
                'data' => null
            ], 200);
        }

        $currentQuantity = $inventoryBalance->getBatchQuantity($request->batch_id);

        if ($currentQuantity < $request->quantity) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Insufficient quantity. Available: ' . $currentQuantity . ', Requested: ' . $request->quantity,
                'data' => null
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            // Remove from lorry inventory
            $inventoryBalance->updateBatchQuantity(
                $request->batch_id,
                $request->quantity,
                'subtract'
            );

            $batch = ProductBatch::find($request->batch_id);
            
            // Update batch quantity
            $batch->quantity += $request->quantity;
            $batch->save();

            // Add to warehouse inventory
            $warehouseInventory = WarehouseInventoryBalance::firstOrCreate(
                [
                    'warehouse_id' => $request->warehouse_id,
                    'product_id' => $batch->product_id,
                    'batch_id' => $request->batch_id,
                ],
                ['quantity' => 0]
            );
            
            $warehouseInventory->increaseQuantity($request->quantity);

            // Create inventory transaction for lorry (stock out)
            InventoryTransaction::create([
                'type' => InventoryTransaction::TYPE_RETURN,
                'warehouse_id' => $request->warehouse_id,
                'lorry_id' => $request->lorry_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => -$request->quantity,
                'date' => now(),
                'user' => $user->name,
                'remark' => 'Returned from van to warehouse'
            ]);

            // Create inventory transaction for warehouse (stock in)
            InventoryTransaction::create([
                'type' => InventoryTransaction::TYPE_STOCK_IN,
                'warehouse_id' => $request->warehouse_id,
                'lorry_id' => $request->lorry_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => $request->quantity,
                'date' => now(),
                'user' => $user->name,
                'remark' => 'Received from van'
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $request->quantity . ' units returned from van to warehouse successfully.',
                'data' => [
                    'quantity_returned' => $request->quantity,
                    'van' => [
                        'id' => $request->lorry_id,
                        'lorry_number' => $lorry->lorryno,
                        'remaining_quantity' => $inventoryBalance->fresh()->getBatchQuantity($request->batch_id)
                    ],
                    'batch' => [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'product_id' => $batch->product_id,
                        'new_quantity' => $batch->quantity
                    ],
                    'warehouse' => [
                        'id' => $request->warehouse_id,
                        'name' => $warehouseInventory->warehouse->name ?? null,
                        'new_quantity' => $warehouseInventory->fresh()->quantity
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Stock Out Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error processing stock out: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiGetAvailableLorry(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $lorries = Lorry::where('status',0)->get();
         
        $lorriesWithInventory = [];
        $grandTotalBatches = 0;
        $grandTotalQuantity = 0;
        
        foreach ($lorries as $lorry) {
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorry->id)->first();
            
            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                
                    // Include lorries without inventory if requested
                    $lorriesWithInventory[] = [
                        'lorry_id' => $lorry->id,
                        'lorry_number' => $lorry->lorryno,
                        'lorry_status' => $lorry->status,
                        'lorry_status_text' => $lorry->status == 0 ? 'Active/In Use' : 'Inactive/Available',
                        'total_batches' => 0,
                        'total_quantity' => 0,
                        'batches' => []
                    ];
               
                continue;
            }
            
            $batches = $inventoryBalance->batches_with_details;
            $totalQuantity = array_sum($inventoryBalance->batches ?? []);
            
            // Format batches
            $formattedBatches = [];
            foreach ($batches as $batch) {
                $formattedBatches[] = [
                    'batch_id' => $batch['batch_id'],
                    'batch_code' => $batch['batch_code'],
                    'unit_code' => $batch['unit_code'],
                    'product_name' => $batch['product_name'],
                    'quantity' => $batch['quantity'],
                    'expiry_date' => $batch['expiry_date'],
                ];
            }
            
            $lorriesWithInventory[] = [
                'lorry_id' => $lorry->id,
                'lorry_number' => $lorry->lorryno,
                'lorry_status' => $lorry->status,
                'lorry_status_text' => $lorry->status == 0 ? 'Active/In Use' : 'Inactive/Available',
                'total_batches' => count($formattedBatches),
                'total_quantity' => $totalQuantity,
                'batches' => $formattedBatches
            ];
            
            $grandTotalBatches += count($formattedBatches);
            $grandTotalQuantity += $totalQuantity;
        }
        
        // Prepare response
        $responseData = [
            'total_lorries' => count($lorriesWithInventory),
            'total_batches' => $grandTotalBatches,
            'total_quantity' => $grandTotalQuantity,
            'lorries' => $lorriesWithInventory
        ];
        
        // Add summary if requested
        if ($request->get('include_summary', false)) {
            $responseData['summary'] = [
                'by_status' => [
                    'active' => $lorries->where('status', 0)->count(),
                    'inactive' => $lorries->where('status', 1)->count()
                ],
                'average_batches_per_lorry' => count($lorriesWithInventory) > 0 
                    ? round($grandTotalBatches / count($lorriesWithInventory), 2) 
                    : 0,
                'average_quantity_per_lorry' => count($lorriesWithInventory) > 0 
                    ? round($grandTotalQuantity / count($lorriesWithInventory), 2) 
                    : 0
            ];
        }
        
        return response()->json([
            'result' => true,
            'message' => __LINE__ . $this->message_separator . 'Vans retrieved successfully',
            'data' => $responseData
        ], 200);
    }

    public function apiGetLorryBatches(Request $request, $id)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        if (!$id) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Lorry Id is required.',
                'data' => null
            ], 200);
        }

        $lorry = Lorry::find($id);
        if (!$lorry) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Van not found.',
                'data' => null
            ], 200);
        }
        $inventoryBalance = InventoryBalance::where('lorry_id', $id)->first();
        
        if (!$inventoryBalance || empty($inventoryBalance->batches)) {
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'No inventory found for this van',
                'data' => [
                    'lorry_id' => $id,
                    'lorry_number' => $lorry->lorryno,
                    'lorry_status' => $lorry->status,
                    'total_batches' => 0,
                    'total_quantity' => 0,
                    'batches' => []
                ]
            ], 200);
        }

        $batches = $inventoryBalance->batches_with_details;
        $totalQuantity = array_sum($inventoryBalance->batches ?? []);
        
        // Format batches with warehouse info if available
        $formattedBatches = [];
        foreach ($batches as $batch) {
            $formattedBatches[] = [
                'batch_id' => $batch['batch_id'],
                'batch_code' => $batch['batch_code'],
                'product_name' => $batch['product_name'],
                'quantity' => $batch['quantity'],
                'expiry_date' => $batch['expiry_date'],
            ];
        }

        return response()->json([
            'result' => true,
            'message' => __LINE__ . $this->message_separator . 'Batches retrieved successfully',
            'data' => [
                'lorry_id' => $request->lorry_id,
                'lorry_number' => $lorry->lorryno,
                'lorry_status' => $lorry->status,
                'total_batches' => count($formattedBatches),
                'total_quantity' => $totalQuantity,
                'batches' => $formattedBatches
            ]
        ], 200);
    }

       
    public function apiCreateWarehouse(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:warehouses,name',
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive' // Optional, defaults to 'active'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            // Create warehouse with default status 'active' if not provided
            $warehouse = Warehouse::create([
                'name' => $request->name,
                'location' => $request->location,
                'status' => $request->status ?? 'active',
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Warehouse created successfully.',
                'data' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'status' => $warehouse->status,
                    'created_at' => $warehouse->created_at,
                    'updated_at' => $warehouse->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Create Warehouse Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error creating warehouse: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }
    
    public function apiUpdateWarehouse(Request $request, $id)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        // Find warehouse
        $warehouse = Warehouse::find($id);
        
        if (!$warehouse) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Warehouse not found',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255|unique:warehouses,name,' . $id,
            'location' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        try {
            // Prepare update data
            $updateData = [];
            
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            
            if ($request->has('location')) {
                $updateData['location'] = $request->location;
            }
            
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            // Update warehouse
            $warehouse->update($updateData);

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Warehouse updated successfully.',
                'data' => [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'location' => $warehouse->location,
                    'status' => $warehouse->status,
                    'updated_at' => $warehouse->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Update Warehouse Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error updating warehouse: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiTransferStock(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'batch_id' => 'required|exists:product_batches,id',
            'quantity' => 'required|integer|min:1',
            'remarks' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Get warehouse details
        $fromWarehouse = Warehouse::find($request->from_warehouse_id);
        $toWarehouse = Warehouse::find($request->to_warehouse_id);
        $batch = ProductBatch::with('product')->find($request->batch_id);

        if (!$fromWarehouse || !$toWarehouse || !$batch) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Invalid warehouse or batch information',
                'data' => null
            ], 200);
        }

        // Check if source warehouse has the inventory
        $sourceInventory = WarehouseInventoryBalance::where('warehouse_id', $request->from_warehouse_id)
            ->where('batch_id', $request->batch_id)
            ->first();

        if (!$sourceInventory) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Batch not found in source warehouse',
                'data' => null
            ], 200);
        }

        if ($sourceInventory->quantity < $request->quantity) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Insufficient quantity in source warehouse. Available: ' . $sourceInventory->quantity . ', Requested: ' . $request->quantity,
                'data' => null
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            // Remove from source warehouse
            $sourceInventory->decreaseQuantity($request->quantity);

            // Add to destination warehouse
            $destInventory = WarehouseInventoryBalance::firstOrCreate(
                [
                    'warehouse_id' => $request->to_warehouse_id,
                    'product_id' => $batch->product_id,
                    'batch_id' => $request->batch_id,
                ],
                ['quantity' => 0]
            );
            $destInventory->increaseQuantity($request->quantity);

            // Create inventory transaction for source warehouse (Stock Out)
            InventoryTransaction::create([
                'warehouse_id' => $request->from_warehouse_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => -$request->quantity, // Negative for outgoing
                'type' => InventoryTransaction::TYPE_TRANSFER,
                'remark' => 'Stock transferred to warehouse: ' . $toWarehouse->name . ($request->remarks ? ' - ' . $request->remarks : ''),
                'date' => now(),
                'user' => $user->name,
            ]);

            // Create inventory transaction for destination warehouse (Stock In)
            InventoryTransaction::create([
                'warehouse_id' => $request->to_warehouse_id,
                'product_id' => $batch->product_id,
                'batch_id' => $request->batch_id,
                'quantity' => $request->quantity, // Positive for incoming
                'type' => InventoryTransaction::TYPE_TRANSFER,
                'remark' => 'Stock received from warehouse: ' . $fromWarehouse->name . ($request->remarks ? ' - ' . $request->remarks : ''),
                'date' => now(),
                'user' => $user->name,
            ]);

            DB::commit();

            // Get updated inventory balances
            $sourceInventory->refresh();
            $destInventory->refresh();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $request->quantity . ' units of ' . $batch->batch_code . ' transferred from ' . $fromWarehouse->name . ' to ' . $toWarehouse->name . ' successfully.',
                'data' => [
                    'transfer_details' => [
                        'quantity' => $request->quantity,
                        'batch_id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'product_id' => $batch->product_id,
                        'product_name' => $batch->product->name,
                        'from_warehouse' => [
                            'id' => $fromWarehouse->id,
                            'name' => $fromWarehouse->name,
                            'remaining_quantity' => $sourceInventory->quantity
                        ],
                        'to_warehouse' => [
                            'id' => $toWarehouse->id,
                            'name' => $toWarehouse->name,
                            'new_quantity' => $destInventory->quantity
                        ],
                        'remarks' => $request->remarks,
                        'transferred_at' => now()->toDateTimeString(),
                        'transferred_by' => $user->name
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Transfer Stock Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error transferring stock: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }   


    public function apiCreateProduct(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'unit_code' => 'required|string|max:255|unique:products,unit_code',
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'required|in:0,1', // 0=inactive, 1=active
            'classification_code' => 'nullable|string|max:50',
            'carton_enabled' => 'nullable|boolean',
            'units_per_carton' => 'nullable|integer|min:1|required_if:carton_enabled,1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Check for quotes in name
        if (str_contains($request->name, '"') || str_contains($request->name, "'")) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'The name cannot contain quotes',
                'data' => null
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            // Create product
            $product = Product::create([
                'unit_code' => $request->unit_code,
                'name' => $request->name,
                'price' => $request->price,
                'cost' => $request->cost,
                'status' => $request->status,
                'type' => $request->type,
                'classification_code' => $request->classification_code,
                'carton_enabled' => $request->carton_enabled ?? false,
                'units_per_carton' => $request->carton_enabled ? $request->units_per_carton : null
            ]);

            // Save initial cost to history if cost is provided
            if (!empty($request->cost)) {
                ProductCost::create([
                    'product_id' => $product->id,
                    'cost' => $request->cost,
                    'old_cost' => null,
                    'changed_by' => $user->id,
                    'remarks' => 'Initial cost',
                    'created_at' => now()
                ]);
            }

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product created successfully',
                'data' => [
                    'id' => $product->id,
                    'unit_code' => $product->unit_code,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'status' => $product->status,
                    'type' => $product->type,
                    'classification_code' => $product->classification_code,
                    'carton_enabled' => $product->carton_enabled,
                    'units_per_carton' => $product->units_per_carton,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Create Product Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error creating product: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiUpdateProduct(Request $request, $id)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $product = Product::find($id);
        
        if (!$product) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Product not found',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'unit_code' => 'sometimes|required|string|max:255|unique:products,unit_code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'status' => 'sometimes|required|in:0,1',
            'type' => 'nullable|integer',
            'classification_code' => 'nullable|string|max:50',
            'carton_enabled' => 'nullable|boolean',
            'units_per_carton' => 'nullable|integer|min:1|required_if:carton_enabled,1',
            'cost_remarks' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Check for quotes in name
        if ($request->has('name') && (str_contains($request->name, '"') || str_contains($request->name, "'"))) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'The name cannot contain quotes',
                'data' => null
            ], 200);
        }

        try {
            // Store old cost for history
            $oldCost = $product->cost;
            
            // Prepare update data
            $updateData = [];
            
            if ($request->has('unit_code')) {
                $updateData['unit_code'] = $request->unit_code;
            }
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('price')) {
                $updateData['price'] = $request->price;
            }
            if ($request->has('cost')) {
                $updateData['cost'] = $request->cost;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            if ($request->has('type')) {
                $updateData['type'] = $request->type;
            }
            if ($request->has('classification_code')) {
                $updateData['classification_code'] = $request->classification_code;
            }
            if ($request->has('carton_enabled')) {
                $updateData['carton_enabled'] = $request->carton_enabled;
            }
            if ($request->has('units_per_carton')) {
                $updateData['units_per_carton'] = $request->carton_enabled ? $request->units_per_carton : null;
            }
            
            // Update product
            $product->update($updateData);
            
            // If cost changed, save to history
            $newCost = $request->cost;
            if ($request->has('cost') && $oldCost != $newCost && !is_null($newCost)) {
                ProductCost::create([
                    'product_id' => $product->id,
                    'cost' => $newCost,
                    'old_cost' => $oldCost,
                    'changed_by' => $user->id,
                    'remarks' => $request->cost_remarks ?? 'Cost updated via API',
                    'created_at' => now()
                ]);
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product updated successfully',
                'data' => [
                    'id' => $product->id,
                    'unit_code' => $product->unit_code,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'status' => $product->status,
                    'type' => $product->type,
                    'classification_code' => $product->classification_code,
                    'carton_enabled' => $product->carton_enabled,
                    'units_per_carton' => $product->units_per_carton,
                    'updated_at' => $product->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Update Product Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error updating product: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiGetProducts(Request $request, $id = null)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // If ID is provided, return single product
            if ($id) {
                $product = Product::with(['batches' => function($query) {
                    $query->where('quantity', '>', 0)
                        ->orderBy('expiry_date', 'asc');
                }])->find($id);
                
                if (!$product) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Product not found',
                        'data' => null
                    ], 200);
                }
                
                // Get cost history
                $costHistory = ProductCost::where('product_id', $product->id)
                    ->with('changer')
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function($item) {
                        return [
                            'date' => $item->created_at->format('Y-m-d H:i:s'),
                            'old_cost' => $item->old_cost,
                            'new_cost' => $item->cost,
                            'changed_by' => $item->changer->name ?? 'System',
                            'remarks' => $item->remarks
                        ];
                    });
                
                // Format batches
                $batches = $product->batches->map(function($batch) {
                    return [
                        'id' => $batch->id,
                        'batch_code' => $batch->batch_code,
                        'quantity' => $batch->quantity,
                        'expiry_date' => $batch->expiry_date,
                        'formatted_expiry_date' => $batch->formatted_expiry_date,
                        'days_to_expiry' => $batch->days_to_expiry,
                        'is_expiring_soon' => $batch->isExpiringSoon(),
                        'status' => $batch->status_text
                    ];
                });
                
                return response()->json([ 
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'Product retrieved successfully',
                    'data' => [
                        'product' => [
                            'id' => $product->id,
                            'unit_code' => $product->unit_code,
                            'name' => $product->name,
                            'price' => $product->price,
                            'cost' => $product->cost,
                            'status' => $product->status,
                            'type' => $product->type,
                            'classification_code' => $product->classification_code,
                            'carton_enabled' => $product->carton_enabled,
                            'units_per_carton' => $product->units_per_carton,
                            'total_quantity' => $product->total_quantity,
                            'active_batches_count' => $product->active_batches_count,
                            'total_batches_count' => $product->total_batches_count,
                            'current_stock_value' => $product->current_stock_value,
                            'expiring_soon_quantity' => $product->expiring_soon_quantity,
                            'expired_quantity' => $product->expired_quantity,
                            'profit_margin' => $product->profit_margin,
                            'profit_amount' => $product->profit_amount,
                            'created_at' => $product->created_at,
                            'updated_at' => $product->updated_at
                        ],
                        'batches' => $batches,
                        'cost_history' => $costHistory,
                        'inventory_summary' => $product->inventory_summary
                    ]
                ], 200);
            }
            
            // Otherwise, return all products with filters
            $query = Product::query();
            
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('unit_code', 'LIKE', "%{$search}%");
                });
            }
            
            if ($request->has('has_stock')) {
                if ($request->has_stock) {
                    $query->has('batches', '>', 0);
                } else {
                    $query->doesntHave('batches');
                }
            }
            
            if ($request->has('expiring_soon')) {
                $query->whereHas('batches', function($q) {
                    $q->where('expiry_date', '<=', now()->addDays(30))
                    ->where('expiry_date', '>', now())
                    ->where('quantity', '>', 0);
                });
            }
            
            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);
            
            // Pagination
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            // Format products
            $formattedProducts = $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'unit_code' => $product->unit_code,
                    'name' => $product->name,
                    'price' => $product->price,
                    'cost' => $product->cost,
                    'status' => $product->status,
                    'type' => $product->type,
                    'classification_code' => $product->classification_code,
                    'carton_enabled' => $product->carton_enabled,
                    'units_per_carton' => $product->units_per_carton,
                    'total_quantity' => $product->total_quantity,
                    'active_batches_count' => $product->active_batches_count,
                    'total_batches_count' => $product->total_batches_count,
                    'current_stock_value' => $product->current_stock_value,
                    'expiring_soon_quantity' => $product->expiring_soon_quantity,
                    'expired_quantity' => $product->expired_quantity,
                    'profit_margin' => $product->profit_margin,
                    'profit_amount' => $product->profit_amount,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            });
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Products retrieved successfully',
                'data' => [
                    'data' => $formattedProducts,
                ]
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('API Get Products Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving products: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiGetUnitCodeOptionsWithCount(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Get unit codes with product count
            $unitCodes = Product::whereNotNull('unit_code')
                ->select('unit_code', \DB::raw('count(*) as product_count'))
                ->groupBy('unit_code')
                ->orderBy('unit_code')
                ->get()
                ->map(function($item) {
                    return [
                        'unit_code' => $item->unit_code,
                        'product_count' => $item->product_count,
                        'display' => $item->unit_code . ' (' . $item->product_count . ' product(s))'
                    ];
                });

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Unit code options retrieved successfully',
                'data' => [
                    'total' => $unitCodes->count(),
                    'unit_codes' => $unitCodes
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Get Unit Code Options Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving unit code options: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function apiGetClassificationCodes(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            $classificationCodes = \App\Models\ClassificationCode::orderBy('code')
                ->get()
                ->map(function($code) {
                    return [
                        'code' => $code->code,
                        'description' => $code->description,
                        'display' => $code->code . ' - ' . $code->description
                    ];
                });

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Classification codes retrieved successfully',
                'data' => [
                    'total' => $classificationCodes->count(),
                    'classification_codes' => $classificationCodes
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Get Classification Codes Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving classification codes: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }


    public function apiCreateProductBatch(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'batch_code' => 'required|string|unique:product_batches,batch_code',
            'expiry_date' => 'required|date',
        ]);

        // Guard: the expiry date must match the date encoded in the batch code.
        // Catches data-entry slips (e.g. a mistyped year) before they are saved.
        $validator->after(function ($validator) use ($request) {
            $expected = ProductBatch::expiryDateFromCode($request->batch_code);
            if ($expected && $request->filled('expiry_date')) {
                $submitted = \Carbon\Carbon::parse($request->expiry_date)->format('Y-m-d');
                if ($submitted !== $expected) {
                    $validator->errors()->add('expiry_date',
                        "Expiry date ({$submitted}) does not match the batch code date ({$expected}). Please check the year.");
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        $input = $request->all();
        $input['status'] = ProductBatch::STATUS_ACTIVE;
        
        DB::beginTransaction();
        
        try {
            // Create product batch
            $productBatch = ProductBatch::create($input);
            
            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product batch created successfully',
                'data' => [
                    'id' => $productBatch->id,
                    'product_id' => $productBatch->product_id,
                    'product_name' => $productBatch->product->name ?? null,
                    'batch_code' => $productBatch->batch_code,
                    'expiry_date' => $productBatch->expiry_date,
                    'formatted_expiry_date' => $productBatch->formatted_expiry_date,
                    'status' => $productBatch->status,
                    'status_text' => $productBatch->status_text,
                    'days_to_expiry' => $productBatch->days_to_expiry,
                    'is_expiring_soon' => $productBatch->isExpiringSoon(),
                    'is_active' => $productBatch->isActive(),
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Create Product Batch Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error creating product batch: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }


    public function apiUpdateProductBatch(Request $request, $id)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $productBatch = ProductBatch::find($id);
        
        if (!$productBatch) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Product batch not found',
                'data' => null
            ], 200);
        }

        $validator = Validator::make($request->all(), [
            'expiry_date' => 'nullable|date|after:today',
            'status' => 'nullable|in:1,2,3' // 1=active, 2=expired, 3=inactive
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        try {
            $updateData = [];
            
            // Update expiry date if provided
            if ($request->has('expiry_date')) {
                $updateData['expiry_date'] = $request->expiry_date;
            }
            
            // Update status if provided
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }
            
            // Only update if there are changes
            if (!empty($updateData)) {
                $productBatch->update($updateData);
            }

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product batch updated successfully',
                'data' => [
                    'id' => $productBatch->id,
                    'product_id' => $productBatch->product_id,
                    'product_name' => $productBatch->product->name ?? null,
                    'batch_code' => $productBatch->batch_code,
                    'expiry_date' => $productBatch->expiry_date,
                    'formatted_expiry_date' => $productBatch->formatted_expiry_date,
                    'quantity' => $productBatch->quantity,
                    'status' => $productBatch->status,
                    'status_text' => $productBatch->status_text,
                    'days_to_expiry' => $productBatch->days_to_expiry,
                    'is_expiring_soon' => $productBatch->isExpiringSoon(),
                    'is_active' => $productBatch->isActive(),
                    'updated_at' => $productBatch->updated_at
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('API Update Product Batch Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error updating product batch: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }


    /**
     * API: Get product batches (single or all) with barcode image
     */
    public function apiGetProductBatches(Request $request, $id = null)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        try {
            // Helper function to generate barcode image base64
            $generateBarcodeImage = function($batchCode) {
                try {
                    // Generate barcode image using DNS1D
                    $barcodeImage = DNS1D::getBarcodePNG($batchCode, 'C128');
                    
                    // Remove the data:image/png;base64, prefix if present
                    if (strpos($barcodeImage, 'base64,') !== false) {
                        return substr($barcodeImage, strpos($barcodeImage, 'base64,') + 7);
                    }
                    
                    return $barcodeImage;
                } catch (\Exception $e) {
                    \Log::error('Barcode generation error for ' . $batchCode . ': ' . $e->getMessage());
                    return null;
                }
            };
            
            // If ID is provided, return single batch
            if ($id) {
                $productBatch = ProductBatch::with(['product', 'inventoryTransactions' => function($query) {
                    $query->orderBy('created_at', 'desc')->limit(50);
                }])->find($id);
                
                if (!$productBatch) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Product batch not found',
                        'data' => null
                    ], 200);
                }
                
                // Generate barcode image for this batch
                $barcodeImage = $generateBarcodeImage($productBatch->batch_code);
                
                // Get warehouse balances for this batch
                $warehouseBalances = WarehouseInventoryBalance::where('batch_id', $productBatch->id)
                    ->with('warehouse')
                    ->get()
                    ->map(function($balance) {
                        return [
                            'warehouse_id' => $balance->warehouse_id,
                            'warehouse_name' => $balance->warehouse->name ?? 'Unknown',
                            'quantity' => $balance->quantity
                        ];
                    });
  
                return response()->json([
                    'result' => true,
                    'message' => __LINE__ . $this->message_separator . 'Product batch retrieved successfully',
                    'data' => [
                        'batch' => [
                            'id' => $productBatch->id,
                            'product_id' => $productBatch->product_id,
                            'product_name' => $productBatch->product->name ?? null,
                            'product_code' => $productBatch->product->unit_code ?? null,
                            'batch_code' => $productBatch->batch_code,
                            'barcode_image' => $barcodeImage, // Base64 encoded barcode
                            'barcode_image_url' => route('productBatches.download-barcode', ['batchCode' => $productBatch->batch_code]), 
                            'expiry_date' => $productBatch->expiry_date,
                            'formatted_expiry_date' => $productBatch->formatted_expiry_date,
                            'quantity' => $productBatch->quantity,
                            'status' => $productBatch->status,
                            'status_text' => $productBatch->status_text,
                            'status_badge_class' => $productBatch->status_badge_class,
                            'days_to_expiry' => $productBatch->days_to_expiry,
                            'is_expired' => $productBatch->isExpired(),
                            'is_expiring_soon' => $productBatch->isExpiringSoon(),
                            'is_active' => $productBatch->isActive(),
                            'has_stock' => $productBatch->hasStock(),
                            'created_at' => $productBatch->created_at,
                            'updated_at' => $productBatch->updated_at
                        ],
                    ]
                ], 200);
            }
            
            // Otherwise, return all batches with filters
            $query = ProductBatch::with('product');
            
            // Apply filters
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('has_stock')) {
                if ($request->has_stock) {
                    $query->where('quantity', '>', 0);
                } else {
                    $query->where('quantity', '<=', 0);
                }
            }
            
            if ($request->has('expired')) {
                if ($request->expired) {
                    $query->expired();
                } else {
                    $query->where('expiry_date', '>', now());
                }
            }
            
            if ($request->has('expiring_soon')) {
                $days = $request->get('expiring_soon_days', 30);
                $query->where('expiry_date', '<=', now()->addDays($days))
                    ->where('expiry_date', '>', now());
            }
            
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('batch_code', 'LIKE', "%{$search}%")
                    ->orWhereHas('product', function($pq) use ($search) {
                        $pq->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('unit_code', 'LIKE', "%{$search}%");
                    });
                });
            }
            
            // Apply sorting
            $sortBy = $request->get('sort_by', 'expiry_date');
            $sortOrder = $request->get('sort_order', 'asc');
            
            if ($sortBy == 'product_name') {
                $query->join('products', 'product_batches.product_id', '=', 'products.id')
                    ->orderBy('products.name', $sortOrder)
                    ->select('product_batches.*');
            } else {
                $query->orderBy($sortBy, $sortOrder);
            }
                        
            // Pagination
            $perPage = $request->get('per_page', 15);
            $batches = $query->paginate($perPage);
            
            // Format batches
            $formattedBatches = $batches->map(function($batch) use ($generateBarcodeImage) {
                $batchData = [
                    'id' => $batch->id,
                    'product_id' => $batch->product_id,
                    'product_name' => $batch->product->name ?? null,
                    'product_code' => $batch->product->unit_code ?? null,
                    'batch_code' => $batch->batch_code,
                    'barcode_image' => $generateBarcodeImage($batch->batch_code),
                    'expiry_date' => $batch->expiry_date,
                    'formatted_expiry_date' => $batch->formatted_expiry_date,
                    'quantity' => $batch->quantity,
                    'status' => $batch->status,
                    'status_text' => $batch->status_text,
                    'days_to_expiry' => $batch->days_to_expiry,
                    'is_expired' => $batch->isExpired(),
                    'is_expiring_soon' => $batch->isExpiringSoon(),
                    'is_active' => $batch->isActive(),
                    'has_stock' => $batch->hasStock(),
                    'created_at' => $batch->created_at,
                    'updated_at' => $batch->updated_at
                ];
                
                
                return $batchData;
            });
            
            $responseData = [
                'data' => $formattedBatches,
                'total' => $batches->total()
            ];
            
            // Add summary statistics
            if ($request->get('include_summary', false)) {
                $responseData['summary'] = [
                    'total_batches' => $batches->total(),
                    'total_quantity' => $batches->sum('quantity'),
                    'active_batches' => $batches->where('status', ProductBatch::STATUS_ACTIVE)->count(),
                    'expired_batches' => $batches->where('status', ProductBatch::STATUS_EXPIRED)->count(),
                    'inactive_batches' => $batches->where('status', ProductBatch::STATUS_INACTIVE)->count(),
                    'expiring_soon_count' => $batches->filter(function($batch) {
                        return $batch->isExpiringSoon();
                    })->count()
                ];
            }
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Product batches retrieved successfully',
                'data' => $responseData
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('API Get Product Batches Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving product batches: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

  
    /**
     * API: Stock in - Add quantity to product batch using batch code
     * Mobile app scans barcode and sends batch_code in request
     */
    public function apiStockIn(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'batch_code' => 'required|string|exists:product_batches,batch_code',
            'quantity' => 'required|integer|min:1',
            'warehouse_id' => 'required|exists:warehouses,id',
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Find product batch by batch code
        $productBatch = ProductBatch::where('batch_code', $request->batch_code)->first();
        
        if (!$productBatch) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Product batch not found with code: ' . $request->batch_code,
                'data' => null
            ], 200);
        }

        DB::beginTransaction();

        try {
            $quantity = $request->quantity;
            $warehouseId = $request->warehouse_id;

            // Stock is not applied here — queue an approval request instead.
            // The batch quantity, warehouse balance and inventory ledger are only
            // updated once an admin approves the request (StockInRequest::approveAndApply).
            StockInRequest::create([
                'source' => StockInRequest::SOURCE_BULK_SCAN,
                'warehouse_id' => $warehouseId,
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'requested_quantity' => $quantity,
                'quantity' => $quantity,
                'remark' => $request->remark ?? 'Stock in from mobile app',
                'status' => StockInRequest::STATUS_PENDING,
                'requested_by' => $user->name,
            ]);

            DB::commit();

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $quantity . ' units submitted for approval',
                'data' => [
                    'batch' => [
                        'id' => $productBatch->id,
                        'batch_code' => $productBatch->batch_code,
                        'product_id' => $productBatch->product_id,
                        'product_name' => $productBatch->product->name ?? null,
                        'product_code' => $productBatch->product->unit_code ?? null,
                        'status' => $productBatch->status_text,
                        'is_active' => $productBatch->isActive(),
                        'expiry_date' => $productBatch->formatted_expiry_date,
                        'days_to_expiry' => $productBatch->days_to_expiry,
                        'is_expiring_soon' => $productBatch->isExpiringSoon()
                    ],
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Stock In Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error processing stock in: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    /**
     * API: Stock out - Remove quantity from product batch using batch code
     * Mobile app scans barcode and sends batch_code in request
     */
    public function apiStockOut(Request $request)
    {
        // Validate session
        $user = User::where('session', $request->header('session'))->first();
        if(empty($user)){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data' => null
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'batch_code' => 'required|string|exists:product_batches,batch_code',
            'quantity' => 'required|integer|min:1',
            'warehouse_id' => 'required|exists:warehouses,id',
            'remark' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . $validator->errors()->first(),
                'data' => null
            ], 200);
        }

        // Find product batch by batch code
        $productBatch = ProductBatch::where('batch_code', $request->batch_code)->first();
        
        if (!$productBatch) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Product batch not found with code: ' . $request->batch_code,
                'data' => null
            ], 200);
        }

        // Check if batch is expired
        if ($productBatch->isExpired()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Cannot remove stock from expired batch',
                'data' => [
                    'batch_code' => $productBatch->batch_code,
                    'expiry_date' => $productBatch->formatted_expiry_date,
                    'current_quantity' => $productBatch->quantity
                ]
            ], 200);
        }

        // Check if requested quantity is available
        if ($productBatch->quantity < $request->quantity) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Insufficient stock. Available: ' . $productBatch->quantity . ', Requested: ' . $request->quantity,
                'data' => [
                    'batch_code' => $productBatch->batch_code,
                    'available_quantity' => $productBatch->quantity,
                    'requested_quantity' => $request->quantity
                ]
            ], 200);
        }

        DB::beginTransaction();
        
        try {
            $quantity = $request->quantity;
            $warehouseId = $request->warehouse_id;
            $warehouse = Warehouse::find($warehouseId);

            // Check if warehouse has this batch with sufficient quantity
            $warehouseInventory = WarehouseInventoryBalance::where('warehouse_id', $warehouseId)
                ->where('batch_id', $productBatch->id)
                ->first();

            if (!$warehouseInventory || $warehouseInventory->quantity < $quantity) {
                $available = $warehouseInventory ? $warehouseInventory->quantity : 0;
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'Insufficient quantity in ' . $warehouse->name . '. Available: ' . $available,
                    'data' => [
                        'batch_code' => $productBatch->batch_code,
                        'warehouse_name' => $warehouse->name,
                        'available_in_warehouse' => $available,
                        'requested_quantity' => $quantity
                    ]
                ], 200);
            }

            // Store old values for response
            $oldQuantity = $productBatch->quantity;
            $oldWarehouseQuantity = $warehouseInventory->quantity;
            
            // Update batch quantity
            $productBatch->decrement('quantity', $quantity);
            
            // Remove from warehouse inventory balance
            $warehouseInventory->decreaseQuantity($quantity);
            
            // Update status based on remaining quantity
            $statusChanged = false;
            $oldStatus = $productBatch->status_text;
            
            if ($productBatch->quantity <= 0) {
                $productBatch->status = ProductBatch::STATUS_INACTIVE;
                $productBatch->save();
                $statusChanged = true;
            }

            // Create inventory transaction
            InventoryTransaction::create([
                'warehouse_id' => $warehouseId,
                'product_id' => $productBatch->product_id,
                'batch_id' => $productBatch->id,
                'quantity' => -$quantity,
                'type' => InventoryTransaction::TYPE_STOCK_OUT,
                'remark' => ($request->remark ?? 'Stock out from mobile app') . ' - Warehouse: ' . $warehouse->name,
                'date' => now(),
                'user' => $user->name
            ]);

            DB::commit();

            $responseData = [
                'result' => true,
                'message' => __LINE__ . $this->message_separator . $quantity . ' units removed from batch successfully',
                'data' => [
                    'batch' => [
                        'id' => $productBatch->id,
                        'batch_code' => $productBatch->batch_code,
                        'product_id' => $productBatch->product_id,
                        'product_name' => $productBatch->product->name ?? null,
                        'product_code' => $productBatch->product->unit_code ?? null,
                        'status' => $productBatch->status_text,
                        'expiry_date' => $productBatch->formatted_expiry_date,
                        'days_to_expiry' => $productBatch->days_to_expiry,
                        'is_expiring_soon' => $productBatch->isExpiringSoon()
                    ],
                ]
            ];

            // Add warning if batch is now depleted
            if ($productBatch->quantity <= 0) {
                $responseData['data']['warning'] = 'Batch is now depleted and has been marked as inactive.';
            }
            
            // Add warning if batch is expiring soon
            if ($productBatch->isExpiringSoon()) {
                $responseData['data']['warning'] = 'Warning: This batch is expiring in ' . $productBatch->days_to_expiry . ' days.';
            }

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('API Stock Out Error: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error processing stock out: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function bulkCreateInvoice(Request $request)
    {
        $driver = Driver::where('session', $request->header('session'))->first();
        if (empty($driver)) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data'    => null
            ], 401);
        }

        // Attribute any Invoice/InvoiceDetail activity log entries below to
        // this driver instead of the "System" fallback (Auth::user() is
        // never set for driver API requests) - cleared automatically by
        // LogApiRequests after this request finishes.
        \App\Models\ActivityLog::actingAs($driver->name);

        if (empty($driver->trip_id)) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                'data'    => null
            ], 200);
        }

        $inventoryCountRecord = InventoryCount::where('driver_id', $driver->id)
            ->where('trip_id', $driver->trip_id)
            ->where('status', InventoryCount::STATUS_APPROVED)
            ->first();

        if ($inventoryCountRecord) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'Driver have completed inventory count, cannot add new invoice, You may continue in next new trip.',
                'data'    => null
            ], 200);
        }

        $invoicesInput = $request->input('invoices', []);

        if (!is_array($invoicesInput)) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'invoices field must be an array',
                'data'    => null
            ], 200);
        }

        // Nothing to sync this round - not an error, just a no-op.
        if (empty($invoicesInput)) {
            return response()->json([
                'result'  => true,
                'message' => __LINE__ . $this->message_separator . 'No invoices to process',
                'data'    => [
                    'total_submitted' => 0,
                    'success_count'   => 0,
                    'fail_count'      => 0,
                    'results'         => [],
                ]
            ], 200);
        }

        $trip = Trip::where('uuid', $driver->trip_id)->where('type', Trip::START_TRIP)->first();
        if (empty($trip)) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                'data'    => null
            ], 200);
        }

        $inventoryBalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->first();

        $results = [];

        foreach ($invoicesInput as $index => $invoiceInput) {
            try {
                $customer = Customer::find($invoiceInput['customer_id'] ?? null);
                if (!$customer) {
                    $results[] = [
                        'index'   => $index,
                        'success' => false,
                        'error'   => 'Customer not found',
                        'input'   => $invoiceInput
                    ];
                    continue;
                }

                $validator = Validator::make($invoiceInput, [
                    'invoiceno'                          => 'nullable|string|max:255',
                    'date'                               => 'date_format:Y-m-d',
                    'customer_id'                        => 'required|numeric',
                    'paymentterm'                        => 'required|numeric|gt:0|lt:6',
                    'remark'                             => 'present|nullable|string',
                    'cheque_no'                          => 'nullable|string',
                    'invoicedetail'                      => 'required|array|min:1',
                    'invoicedetail.*.product_id'         => 'required|numeric',
                    'invoicedetail.*.product_batch_id'   => 'required|numeric',
                    'invoicedetail.*.quantity'           => 'required|numeric|min:1',
                    'invoicedetail.*.price'              => 'required|numeric|min:0',
                    'invoicedetail.*.discount'           => 'nullable|numeric|min:0',
                ]);

                if ($validator->fails()) {
                    $results[] = [
                        'index'   => $index,
                        'success' => false,
                        'errors'  => $validator->errors()->toArray(),
                        'input'   => $invoiceInput
                    ];
                    continue;
                }

                $itemsToProcess = [];
                $itemsToIgnore  = [];
                $invalidBatches = [];

                foreach ($invoiceInput['invoicedetail'] as $item) {
                    $productBatch = ProductBatch::where('id', $item['product_batch_id'])
                        ->where('product_id', $item['product_id'])
                        ->first();

                    if (empty($productBatch)) {
                        $invalidBatches[] = [
                            'product_id'       => $item['product_id'],
                            'product_batch_id' => $item['product_batch_id'],
                            'error'            => 'Invalid product batch or product ID mismatch',
                        ];
                        continue;
                    }

                    $availableQuantity = $inventoryBalance ? $inventoryBalance->getBatchQuantity($item['product_batch_id']) : 0;

                    if ($availableQuantity > 0) {
                        $itemsToProcess[] = [
                            'item'         => $item,
                            'productBatch' => $productBatch,
                        ];
                    } else {
                        $itemsToIgnore[] = [
                            'item'         => $item,
                            'productBatch' => $productBatch,
                        ];
                    }
                }

                if (!empty($invalidBatches)) {
                    $results[] = [
                        'index'          => $index,
                        'success'        => false,
                        'error'          => 'Invalid product batches found',
                        'invalid_batches'=> $invalidBatches,
                        'input'          => $invoiceInput
                    ];
                    continue;
                }

                DB::beginTransaction();

                $requestedInvoiceNo = !empty($invoiceInput['invoiceno']) && $invoiceInput['invoiceno'] !== 'SYSTEM GENERATED IF BLANK'
                    ? $invoiceInput['invoiceno']
                    : null;

                // Create invoice - retry with a freshly generated number if it collides
                // with a concurrently-created one (race condition guard)
                $invoice = null;
                $invoiceAttempts = 0;
                $useRequestedInvoiceNo = true;

                while (!$invoice) {
                    $invoiceAttempts++;

                    try {
                        $invoice = DB::transaction(function () use ($invoiceInput, $customer, $driver, $requestedInvoiceNo, $useRequestedInvoiceNo) {
                            $invoiceNo = ($useRequestedInvoiceNo && $requestedInvoiceNo && !Invoice::where('invoiceno', $requestedInvoiceNo)->exists())
                                ? $requestedInvoiceNo
                                : Invoice::generateInvoiceNumber($driver->id);

                            $newInvoice = new Invoice();
                            $newInvoice->invoiceno   = $invoiceNo;
                            $newInvoice->date        = $invoiceInput['date'] ?? date('Y-m-d H:i:s');
                            $newInvoice->customer_id = $customer->id;
                            $newInvoice->driver_id   = $driver->id;
                            $newInvoice->paymentterm = $invoiceInput['paymentterm'];
                            $newInvoice->chequeno    = $invoiceInput['cheque_no'] ?? null;
                            $newInvoice->remark      = $invoiceInput['remark'] ?? null;
                            $newInvoice->trip_uuid   = $driver->trip_id;
                            $newInvoice->status      = Invoice::STATUS_COMPLETED;
                            $newInvoice->save();

                            return $newInvoice;
                        });
                    } catch (\Illuminate\Database\QueryException $e) {
                        if ($invoiceAttempts >= 5 || !Invoice::isDuplicateInvoiceNumberException($e)) {
                            throw $e;
                        }
                        $useRequestedInvoiceNo = false;
                    }
                }

                $totalprice = 0;

                foreach ($itemsToProcess as $validItem) {
                    $item = $validItem['item'];

                    $invoiceDetail                   = new InvoiceDetail();
                    $invoiceDetail->invoice_id       = $invoice->id;
                    $invoiceDetail->product_id       = $item['product_id'];
                    $invoiceDetail->product_batch_id = $item['product_batch_id'];
                    $invoiceDetail->quantity         = $item['quantity'];
                    $invoiceDetail->price            = $item['price'];
                    $invoiceDetail->discount         = $item['discount'] ?? 0;
                    $invoiceDetail->totalprice       = ($item['quantity'] * $item['price']) - ($item['discount'] ?? 0);
                    $invoiceDetail->remark           = $item['remark'] ?? null;
                    // Stock is deducted from the lorry below - without this
                    // flag, a later editInvoice() call has no way to know
                    // this item's stock was ever taken and would silently
                    // skip restoring it.
                    $invoiceDetail->deducted_from_inventory = true;
                    $invoiceDetail->save();

                    $totalprice += $invoiceDetail->totalprice;

                    $inventoryTransaction             = new InventoryTransaction();
                    $inventoryTransaction->type       = InventoryTransaction::TYPE_STOCK_OUT;
                    $inventoryTransaction->product_id = $item['product_id'];
                    $inventoryTransaction->batch_id   = $item['product_batch_id'];
                    $inventoryTransaction->quantity   = -$item['quantity'];
                    $inventoryTransaction->date       = now();
                    $inventoryTransaction->lorry_id   = $trip->lorry_id;
                    $inventoryTransaction->user       = $driver->name;
                    $inventoryTransaction->remark     = 'Invoice #' . $invoice->invoiceno . ' - Sale of product';
                    $inventoryTransaction->save();

                    if ($inventoryBalance) {
                        $inventoryBalance->updateBatchQuantity($item['product_batch_id'], $item['quantity'], 'subtract');
                    }
                }

                foreach ($itemsToIgnore as $ignoredItem) {
                    $item = $ignoredItem['item'];

                    $invoiceDetail                   = new InvoiceDetail();
                    $invoiceDetail->invoice_id       = $invoice->id;
                    $invoiceDetail->product_id       = $item['product_id'];
                    $invoiceDetail->product_batch_id = $item['product_batch_id'];
                    $invoiceDetail->quantity         = $item['quantity'];
                    $invoiceDetail->price            = $item['price'];
                    $invoiceDetail->discount         = $item['discount'] ?? 0;
                    $invoiceDetail->totalprice       = ($item['quantity'] * $item['price']) - ($item['discount'] ?? 0);
                    $invoiceDetail->remark           = $item['remark'] ?? null;
                    $invoiceDetail->deducted_from_inventory = false;
                    $invoiceDetail->save();

                    $totalprice += $invoiceDetail->totalprice;
                }

                if ($invoiceInput['paymentterm'] == 1) {
                    $invoicePayment              = new InvoicePayment();
                    $invoicePayment->invoice_id  = $invoice->id;
                    // Cash (non-credit) -> auto-sync as its own single-invoice AR Payment.
                    $invoicePayment->payment_batch_id = (string) \Illuminate\Support\Str::uuid();
                    $invoicePayment->autocount_status = InvoicePayment::AC_PENDING;
                    $invoicePayment->type        = 1;
                    $invoicePayment->customer_id = $invoice->customer_id;
                    $invoicePayment->amount      = $totalprice;
                    $invoicePayment->status      = 1;
                    $invoicePayment->driver_id   = $driver->id;
                    $invoicePayment->approve_by  = $driver->name;
                    $invoicePayment->approve_at  = date('Y-m-d H:i:s');
                    $invoicePayment->save();
                }

                Task::where('customer_id', $customer->id)
                    ->where('driver_id', $driver->id)
                    ->update(['status' => 8]);

                DB::commit();

                $results[] = [
                    'index'           => $index,
                    'success'         => true,
                    'invoiceno'       => $invoice->invoiceno,
                    'invoice_id'      => $invoice->id,
                    'date'            => $invoice->date,
                    'customer_id'     => $invoice->customer_id,
                    'customer_name'   => $customer->company,
                    'total'           => $totalprice,
                    'paymentterm'     => $invoice->paymentterm,
                    'status'          => $invoice->status,
                    'payment_created' => $invoiceInput['paymentterm'] == 1,
                    'items_count'     => count($itemsToProcess) + count($itemsToIgnore),
                    'created_at'      => $invoice->created_at->format('Y-m-d H:i:s'),
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Bulk invoice creation failed at index ' . $index . ': ' . $e->getMessage());
                $results[] = [
                    'index'   => $index,
                    'success' => false,
                    'error'   => $e->getMessage(),
                    'input'   => $invoiceInput
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();
        $failCount    = collect($results)->where('success', false)->count();

        return response()->json([
            'result'  => true,
            'message' => __LINE__ . $this->message_separator . "Bulk create completed: {$successCount} succeeded, {$failCount} failed",
            'data'    => [
                'total_submitted' => count($invoicesInput),
                'success_count'   => $successCount,
                'fail_count'      => $failCount,
                'results'         => $results,
            ]
        ], 200);
    }

    public function getOfflineCustomerList(Request $request)
    {
        $driver = Driver::where('session', $request->header('session'))->first();
        if (empty($driver)) {
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'api.message.invalid_session',
                'data'    => null
            ], 401);
        }

        try {
            $assignedCustomerIds = Assign::where('driver_id', $driver->id)
                ->active()
                ->whereHas('customer', function ($q) { $q->where('status', 1); })
                ->pluck('customer_id')
                ->toArray();

            if (empty($assignedCustomerIds)) {
                return response()->json([
                    'result'  => false,
                    'message' => __LINE__ . $this->message_separator . 'No customers assigned to driver',
                    'data'    => null
                ], 200);
            }

            $customers = Customer::select('id', 'company', 'paymentterm', 'phone', 'billing_address', 'delivery_address')
                ->whereIn('id', $assignedCustomerIds)
                ->orderBy('company')
                ->get();

            $products = Product::select('id', 'unit_code', 'name', 'price')
                ->where('status', 1)
                ->orderBy('name')
                ->get()
                ->map(fn($p) => [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'unit_code'     => $p->unit_code,
                    'default_price' => (float) $p->price,
                ]);

            // Only load special prices for assigned customers to keep the dataset small
            $specialPricesByCustomer = SpecialPrice::where('status', 1)
                ->whereIn('customer_id', $assignedCustomerIds)
                ->get()
                ->groupBy('customer_id')
                ->map(fn($rows) => $rows->keyBy('product_id')->map(fn($sp) => (float) $sp->price));

            $customerList = $customers->map(fn($customer) => [
                'id'               => $customer->id,
                'company'          => $customer->company,
                'phone'            => $customer->phone,
                'paymentterm'      => $customer->paymentterm,
                'billing_address'  => $customer->billing_address,
                'delivery_address' => $customer->delivery_address,
                // keyed by product_id — only entries where a special price exists
                'special_prices'   => $specialPricesByCustomer->get($customer->id, collect()),
            ])->values();

            // Predicted next OFFLINE running number for this driver's current
            // month, so the offline app can construct invoice numbers locally
            // without hitting the server for every invoice while offline.
            // Offline-created invoicenos are marked with an "A" + 3-digit
            // running number (e.g. I2608/AH1/A001) so they're visibly
            // distinct from normally (online) generated ones and never
            // collide with them. Only the plain 3-digit number is returned -
            // the app itself prepends the "A" when building the full
            // invoiceno. This is only a prediction - if it's since been
            // taken by the time the driver syncs, invoice creation already
            // retries with a fresh number (see the duplicate-invoiceno retry
            // loop in addinvoice()/bulkCreateInvoice()).
            $userCode = $driver->invoice_code ?? '';
            $prefix = 'I' . date('y') . date('m') . '/' . $userCode . '/';

            $lastOfflineInvoice = Invoice::where('invoiceno', 'like', $prefix . 'A%')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastOfflineInvoice) {
                $lastOfflineNumber = (int) substr($lastOfflineInvoice->invoiceno, strlen($prefix) + 1);
                $nextOfflineNumber = $lastOfflineNumber + 1;
            } else {
                $nextOfflineNumber = 1;
            }

            $runningNumber = str_pad($nextOfflineNumber, 3, '0', STR_PAD_LEFT);

            return response()->json([
                'result'  => true,
                'message' => __LINE__ . $this->message_separator . 'Offline customer list retrieved successfully',
                'data'    => [
                    'total_customers'     => $customerList->count(),
                    'products'            => $products->values(),
                    'customers'           => $customerList,
                    'invoice_html'        => $this->getinvoiceHtml(),
                    'invoice_code'        => $userCode,
                    'running_number'      => $runningNumber,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('getOfflineCustomerList failed: ' . $e->getMessage());
            return response()->json([
                'result'  => false,
                'message' => __LINE__ . $this->message_separator . 'Error retrieving offline customer list: ' . $e->getMessage(),
                'data'    => null
            ], 200);
        }
    }

    private function getinvoiceHtml(): string
    {
        return <<<'HTML'
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>GW Noodles</title>
            <style>
                @page {
            margin: 0;
            size: auto;
        }
        html {
            margin: 0;
            padding: 0;
        }
        body {
            page-break-inside: avoid;
            font-size: 22px;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.3;
            margin: 0 10px;
            padding: 0;
            width: auto;
        }
        .receipt-container {
            width: 100%;
        }
        table{
            width: 100%;
            border-collapse: collapse;
        }
        table td, table th{
            padding: 2px 0;
            vertical-align: top;
        }
        .company{
            font-weight: bold;
            text-align: center;
            font-size: 18px;
            margin: 0 0 2px 0;
            text-transform: uppercase;
            width: 100%;
        }
        .address{
            text-align: center;
            font-size: 18px;
            margin: 1px 0;
            white-space: normal;
            word-break: break-word;
            width: 100%;
        }
        .header-line{
            text-align: center;
            margin: 5px 0;
            font-size: 18px;
            width: 100%;
        }
        .divider{
            border-top: 1px dashed #000;
            margin: 8px 0;
            width: 100%;
        }
        .divider-solid{
            border-top: 1px solid #000;
            margin: 8px 0;
            width: 100%;
        }
        .ta-r{
            text-align: right;
        }
        .ta-l{
            text-align: left;
        }
        .ta-c{
            text-align: center;
        }
        p{
            margin: 0;
        }
        .item-row td{
            padding: 1px 0;
            font-size: 18px;
        }
        .item-name{
            font-size: 18px;
            padding-left: 5px;
            color: #000;
            font-weight: bold;
        }
        .totals td{
            padding: 3px 0;
            font-size: 18px;
            font-weight: bold;

        }
        .address-block{
            font-size: 16px;
            margin: 2px 0;
            white-space: normal;
            word-wrap: break-word;
            text-align: center;
        }
        .address-label{
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .document-info {
            width: 100%;
            margin: 5px 0;
            font-size: 18px;
        }
        .document-info td {
            padding: 1px 0;
        }
        .items-summary {
            width: 100%;
            margin: 5px 0;
        }
        .items-summary td {
            padding: 2px 0;
        }
        .itemize-header {
            font-weight: bold;
            margin: 10px 0 5px 0;
            text-align: left;
            text-decoration: underline;
        }
        .total-count {
            text-align: right;
            margin: 5px 0;
            font-weight: normal;
        }
        .payment-section {
            width: 100%;
            margin: 8px 0;
        }
        .payment-section td {
            padding: 2px 0;
        }
        .footer-section {
            margin-top: 10px;
            text-align: center;
        }
        /* Added bold class for customer name */
        .customer-name-bold {
            font-weight: bold !important;
        }
        /* Added bold class for totals section */
        .totals-section-bold {
            font-weight: bold !important;
        }
        @media print {
            .item-name {
                font-size: 12pt !important;
                font-weight: bold !important;
                color: black !important;
            }
            
            body, td, div, span {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
            </style>
        </head>
        <body>

            <div class="receipt-container">
                <!-- Company Header -->
                <div class="company">GW NOODLES SDN BHD</div>
                <div class="address">(201601033587)</div>
                <div class="address">23, JALAN SETIA PERNIAGAAN 9,</div>
                <div class="address">TAMAN SETIA PERNIAGAAN,</div>
                <div class="address">(TEL)+60167237931</div>

                <div class="divider"></div>

                <!-- Sale Type -->
                <div class="header-line">
                    <strong>@paymentMethod</strong>
                </div>

                <!-- Document Info -->
                <table class="document-info">
                    <tr>
                        <td class="ta-l">Document #:</td>
                        <td class="ta-r">@invoiceNo</td>
                    </tr>
                    <tr>
                        <td class="ta-l">Date :</td>
                        <td class="ta-r">@invoiceDate</td>
                    </tr>
                    <tr>
                        <td class="ta-l">S/Driver :</td>
                        <td class="ta-r">@driverName</td>
                    </tr>
                    
                    @checkPaymentPanel

                </table>

                <div class="divider"></div>

                <!-- Billing Address -->
                <div class="address-label">BILLING TO :</div>
                <div class="address-block">
                    <!-- CUSTOMER NAME IN BOLD - First requirement -->
                    <strong class="customer-name-bold" style="font-weight: bold;">@companyName</strong><br>
                    (@companyPhone)<br>
                    @billingAddress
                </div>

                <div style="margin-top: 8px;"><span class="address-label">DELIVERY TO :</span></div>
                <div class="address-block">
                    @deliveryAddress
                </div>

                <div class="divider"></div>

                <!-- Items Summary -->
                <table class="items-summary">
                    <tr>
                        <td class="ta-l">total @itemCount items :</td>
                        <td class="ta-r" style="font-weight: bold;">@totalAmount</td>
                    </tr>
                </table>

                <div class="divider-solid"></div>

                <!-- Items Detail -->
                <div class="itemize-header">Items :</div>
                
                <!-- Item table. Columns: gross unit Price | per-line Disc | Qty | Amount,
                     so Price x Qty - Disc = Amount. The app fills @items with one
                     row per line (matching this 4-column layout) - un-discounted
                     lines show "-" in the Disc column. -->
                <table style="margin-bottom: 5px;">
                    <thead>
                        <tr style="font-weight: bold;">
                            <td class="ta-l" style="width: 22%;">Price</td>
                            <td class="ta-r" style="width: 22%;">Disc</td>
                            <td class="ta-r" style="width: 30%;">Qty</td>
                            <td class="ta-r" style="width: 26%;">Amount</td>
                        </tr>
                    </thead>
                    <tbody>

                        @items

                    </tbody>
                </table>
                <div class="divider"></div>
                <table>
                    <tbody>
                        <tr class="item-row">
                            <td class="ta-l" style="width: 22%;"></td>
                            <td class="ta-r" style="width: 22%;">@totalDiscount</td>
                            <td class="ta-r" style="width: 30%;">@totalQuantity</td>
                            <td class="ta-r" style="width: 26%;">@totalAmount</td>
                        </tr>
                    </tbody>
                </table>
                <div class="divider"></div>

                <!-- Totals - Second requirement with bold text -->
                <table class="totals totals-section-bold">
                    <tr>
                        <td class="ta-r">Total :</td>
                        <td class="ta-r" style="font-size: 20px; font-weight: bold;">RM @totalAmount</td>
                    </tr>
                </table>

                <div class="divider-solid"></div>
                <div class="divider"></div>
            </div>
        </body>
        </html>
        HTML;
    }
}
