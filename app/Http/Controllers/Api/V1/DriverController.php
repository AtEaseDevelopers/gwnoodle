<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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

                $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
                if(!empty($trip)){
                    if($trip->type == 2){
                        $status = false;
                    }else{
                        $status = true;
                    }
                }else{
                    $status = false;
                }

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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => [
                            'status' => false
                        ]
                    ], 200);
                }else{
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                        'data' => [
                            'status' => true,
                            'trip' => $trip
                        ]
                    ], 200);
                }
            }else{
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => [
                        'status' => false
                    ]
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
            //store lorry status to 0 when in use
            $lorry->status = 0;
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

    // public function endtrip(Request $request){
    //     try{
    //         $data = $request->all();
    //         //check session
    //         $driver = Driver::where('session', $request->header('session'))->first();
    //         if(empty($driver)){
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'Invalid session',
    //                 'data' => null
    //             ], 401);
    //         }
    //         //validation
    //         $validator = Validator::make($request->all(), [
    //             // 'kelindan_id' => 'required|numeric',
    //             'lorry_id' => 'required|numeric',
    //         ]);
    //         if ($validator->fails()) {
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
    //                 'data' => null
    //             ], 400);
    //         }
    //         // $kelindan = Kelindan::where('id', $data['kelindan_id'])->first();
    //         // if(empty($kelindan)){
    //         //     return response()->json([
    //         //         'result' => false,
    //         //         'message' => __LINE__.$this->message_separator.'Invalid Kelindan',
    //         //         'data' => null
    //         //     ], 400);
    //         // }
    //         $lorry = Lorry::where('id', $data['lorry_id'])->first();
    //         if(empty($lorry)){
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'Invalid Lorry',
    //                 'data' => null
    //             ], 400);
    //         }
    //         //process
    //         DB::beginTransaction();
    //         $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
    //         if(!empty($trip)){
    //             if($trip->type == 2){
    //                 DB::rollback();
    //                 return response()->json([
    //                     'result' => false,
    //                     'message' => __LINE__.$this->message_separator.'Trip had not started',
    //                     'data' => null
    //                 ], 400);
    //             }else{
    //                 $newtrip = new Trip();  
    //                 $newtrip->driver_id = $driver->id;   
    //                 $newtrip->kelindan_id = $data['kelindan_id'];
    //                 $newtrip->lorry_id = $data['lorry_id'];
    //                 $newtrip->cash = $data['cash'];
    //                 $newtrip->advance_amount = $data['advance_amount'] ?? 0;
    //                 $newtrip->type = 2;
    //                 $newtrip->date = date("Y-m-d H:i:s");
    //                 $newtrip->save();
    //                 //cancelled task
    //                 $task = Task::where('driver_id', $driver->id)->where('date',date('Y-m-d'))->whereIn('status',[0,1])->update(['trip_id'=>$newtrip->id,'status' => 9]);
    //                 foreach($data["wastage"] as $wastage) {
    //                     $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->where('product_id',$wastage['product_id'])->first();
    //                     if(empty($inventorybalance)){
    //                         DB::rollback();
    //                         return response()->json([
    //                             'result' => false,
    //                             'message' => __LINE__.$this->message_separator.'Wastage quantity more than available quantity',
    //                             'data' => null
    //                         ], 400);
    //                     }else{
    //                         if($inventorybalance->quantity < $wastage["quantity"]){
    //                             DB::rollback();
    //                             return response()->json([
    //                                 'result' => false,
    //                                 'message' => __LINE__.$this->message_separator.'Wastage quantity more than available quantity',
    //                                 'data' => null
    //                             ], 400);
    //                         }else{
    //                             $inventorybalance->quantity = $inventorybalance->quantity - $wastage["quantity"];
    //                             $inventorybalance->save();
    //                             $inventorytransaction = New InventoryTransaction();
    //                             $inventorytransaction->lorry_id = $trip->lorry_id;
    //                             $inventorytransaction->product_id = $wastage["product_id"];
    //                             $inventorytransaction->quantity = $wastage["quantity"] * -1;
    //                             $inventorytransaction->type = 5;
    //                             $inventorytransaction->date = date('Y-m-d H:i:s');
    //                             $inventorytransaction->user = $driver->employeeid . " (" . $driver->name . ")";
    //                             $inventorytransaction->save();
    //                         }
    //                     }
    //                 }
    //                 DB::commit();
    //                 return response()->json([
    //                     'result' => true,
    //                     'message' => __LINE__.$this->message_separator.'Trip had been ended successfully',
    //                     'data' => $newtrip
    //                 ], 200);
    //             }
    //         }else{
    //             DB::rollback();
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'Trip had not started',
    //                 'data' => null
    //             ], 400);
    //         }
    //     }
    //     catch(Exception $e){
    //         return response()->json([
    //             'result' => false,
    //             'message' => __LINE__.$this->message_separator.$e->getMessage(),
    //             'data' => null
    //         ], 500);
    //     }
    // }

    public function tripEnd(Request $request)
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
        if($driver->trip_id == NULL ){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver have to start trip before end trip.',
                'data' => null
            ], 200);
        }
        $inventoryCount = InventoryCount::where('driver_id', $driver->id)->where('trip_id',$driver->trip_id)->where('status', InventoryCount::STATUS_APPROVED)->first();

        if(!$inventoryCount ){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver have to complete Stock Out before end trip.',
                'data' => null
            ], 200);
        }
        $latestTrip = Trip::where('driver_id', $driver->id)
                ->where('uuid', $driver->trip_id)
                ->where('type', Trip::START_TRIP)
                ->first();

        try {

            $trip = Trip::create([
                'uuid'=> $driver->trip_id,
                'lorry_id'=> $latestTrip->lorry_id,
                'date'=> now(),
                'driver_id' => $driver->id,
                'type' => Trip::END_TRIP,
            ]); 
            $lorry = Lorry::where('id', $latestTrip->lorry_id)->first();
            //store lorry status to 0 when in use
            $lorry->status = 1;
            $lorry->save();

            $driver->trip_id = NULL;
            $driver->save();
            //return stock to warehouse
            
            
            return response()->json([
                'success' => true,
                'message' => 'Driver End Trip successfully.',
                'data' => ''
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to end trip: ' . $e->getMessage()
            ], 500);
        }
    }

    public function trip(Request $request){
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
            'kelindan_id' => 'required|numeric',
            'lorry_id' => 'required|numeric',
            'type' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                'data' => null
            ], 400);
        }
        // $kelindan = Kelindan::where('id', $data['kelindan_id'])->first();
        // if(empty($kelindan)){
        //     return response()->json([
        //         'result' => false,
        //         'message' => __LINE__.$this->message_separator.'Invalid Kelindan',
        //         'data' => null
        //     ], 400);
        // }
        $lorry = Lorry::where('id', $data['lorry_id'])->first();
        if(empty($lorry)){
            return response()->json([
                'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_lorry',
                'data' => null
            ], 400);
        }
        if(!($data['type'] == 1 || $data['type'] == 2)){
            return response()->json([
               'result' => false,
                'message' => __LINE__.$this->message_separator.'api.message.invalid_type',
                'data' => null
            ], 400);
        }
        //process
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if($data['type'] == 1){
            if(!empty($trip)){
                if($trip->type == 2){
                    //insert trip
                    $newtrip = new Trip();
                    $newtrip->driver_id = $driver->id;
                    $newtrip->kelindan_id = $data['kelindan_id'];
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->type = 1;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //generate task
                    $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                    $count = 1;
                    foreach($assigns as $assign){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $assign['customer_id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->save();
                        $count = $count + 1;
                    }
                    $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                    foreach($invoices as $invoice){
                        $task = new Task();
                        $task->date = date("Y-m-d");
                        $task->driver_id = $driver->id;
                        $task->customer_id = $invoice['customer_id'];
                        $task->invoice_id = $invoice['id'];
                        $task->sequence = $count;
                        $task->status = 0;
                        $task->save();
                        $count = $count + 1;
                    }
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                        'data' => $newtrip
                    ], 200);
                }else{
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_started',
                        'data' => null
                    ], 401);
                }
            }else{
                //insert trip
                $newtrip = new Trip();
                $newtrip->driver_id = $driver->id;
                $newtrip->kelindan_id = $data['kelindan_id'];
                $newtrip->lorry_id = $data['lorry_id'];
                $newtrip->type = 1;
                $newtrip->date = date("Y-m-d H:i:s");
                $newtrip->save();
                //generate task
                $assigns = Assign::where('driver_id', $driver->id)->orderby('sequence','asc')->get()->toarray();
                $count = 1;
                foreach($assigns as $assign){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $assign['customer_id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                    $count = $count + 1;
                }
                $invoices = Invoice::where('driver_id', $driver->id)->where('status',0)->where('date',date('Y-m-d'))->get()->toarray();
                foreach($invoices as $invoice){
                    $task = new Task();
                    $task->date = date("Y-m-d");
                    $task->driver_id = $driver->id;
                    $task->customer_id = $invoice['customer_id'];
                    $task->invoice_id = $invoice['id'];
                    $task->sequence = $count;
                    $task->status = 0;
                    $task->save();
                    $count = $count + 1;
                }
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_started_successfully',
                    'data' => $newtrip
                ], 200);
            }
        }else if($data['type'] == 2){
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 401);
                }else{
                    $newtrip = new Trip();
                    $newtrip->driver_id = $driver->id;
                    $newtrip->kelindan_id = $data['kelindan_id'];
                    $newtrip->lorry_id = $data['lorry_id'];
                    $newtrip->type = 2;
                    $newtrip->date = date("Y-m-d H:i:s");
                    $newtrip->save();
                    //cancelled task
                    $task = Task::where('driver_id', $driver->id)->where('date',date('Y-m-d'))->whereIn('status',[0,1])->update(['status' => 9]);
                    return response()->json([
                        'result' => true,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_been_ended_successfully',
                        'data' => $newtrip
                    ], 200);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
        }
    }

    //Kelindan
    // public function getkelindan(Request $request){
    //     try{
    //         $data = $request->all();
    //         //check session
    //         $driver = Driver::where('session', $request->header('session'))->first();
    //         if(empty($driver)){
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'api.message.invalid_session',
    //                 'data' => null
    //             ], 401);
    //         }
    //         //process
    //         // $kelindan = Kelindan::where('status',1)->select('id','name')->get()->toarray();
    //         $kelindan = DB::select("select k.id, k.name from kelindans k left join ( select driver_id, type, kelindan_id from trips where id in ( select max(id) as id from trips group by driver_id ) ) b on k.id = b.kelindan_id and b.type = 1 where b.kelindan_id is null;");
    //         if(count($kelindan) != 0){
    //             return response()->json([
    //                 'result' => true,
    //                 'message' => __LINE__.$this->message_separator.'api.message.kelindan_found',
    //                 'data' => $kelindan
    //             ], 200);
    //         }else{
    //             return response()->json([
    //                 'result' => false,
    //                 'message' => __LINE__.$this->message_separator.'api.message.kelindan_not_found',
    //                 'data' => null
    //             ], 200);
    //         }
    //     }
    //     catch(Exception $e){
    //         return response()->json([
    //             'result' => false,
    //             'message' => __LINE__.$this->message_separator.$e->getMessage(),
    //             'data' => null
    //         ], 500);
    //     }
    // }

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
            $lorry = DB::select("select l.id, l.lorryno from lorrys l left join ( select driver_id, type, lorry_id from trips where id in (select max(id) as id from trips group by driver_id) ) b on l.id = b.lorry_id and b.type = 1 where b.lorry_id is null;");
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

    //Task
    public function gettask(Request $request){
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $task = Task::where('driver_id', $driver->id)
                ->where('date',date('Y-m-d'))
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhere('trip_id', null);
                })
                // ->whereIn('trip_id',[NULL,$trip->id])
                ->with('customer.activefoc')
                ->with('invoice.invoicedetail.product:id,code,name')
                ->get()->toarray();
            if(count($task) != 0){
                $message = true;
                foreach($task as $c=>$t){
                    if(asset($t['customer']['id'])){
                        $task[$c]['customer']['credit'] = round(  (DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$t['customer']['id'].');')[0]->credit ?? 0) ,2);
                        // $task[$c]['customer']['credit'] = $t['customer']['id'];
                        $task[$c]['customer']['product'] = DB::table('products')
                            ->leftJoin('special_prices', function($join) use($t)
                                {
                                    $join->on('special_prices.customer_id','=',DB::raw("'".$t['customer']['id']."'"));
                                    $join->on('special_prices.product_id', '=', 'products.id');
                                    $join->on('special_prices.status', '=', DB::raw("'1'"));
                                })
                            ->where('products.status','1')
                            ->select('products.id','products.code','products.name',DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                            ->get();
                        $task[$c]['customer']['groupcompany'] = DB::table('companies')
                            ->where('companies.group_id',explode(',',$t['customer']['group'])[0])
                            ->select('companies.*')
                            ->first() ?? null;
                    }
                }
            }else{
                $message = false;
            }
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->with('product')->get()->toarray();
            if($message){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_found',
                    'data' => [
                        'task' => $task,
                        'stock' => $inventorybalance
                    ]
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_not_found',
                    'data' => [
                        'task' => null,
                        'stock' => $inventorybalance
                    ]
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

    public function gettaskpage(Request $request){
        try{
            $data = $request->all();
            $size = 20;
            if(isset($data['size']))
            {
                $size = $data['size'];
            }
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $task = Task::where('driver_id', $driver->id)
                ->where('date',date('Y-m-d'))
                //->where('status','!=',9)
                //->where('status','!=',0)
                ->where(function ($query) use ($trip) {
                    $query->where('trip_id', $trip->id)
                        ->orWhere('trip_id', null);
                })
                // ->whereIn('trip_id',[NULL,$trip->id])
                ->with('customer.activefoc')
                ->with('invoice.invoicedetail.product:id,code,name')
                ->paginate($size);

            if(count($task) != 0){
                $message = true;
                foreach($task as $c=>$t){
                    if(asset($t['customer']['id'])){
                        $task[$c]['customer']['credit'] = round(  (DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$t['customer']['id'].');')[0]->credit ?? 0) ,2);
                        // $task[$c]['customer']['credit'] = $t['customer']['id'];
                        $task[$c]['customer']['product'] = DB::table('products')
                            ->leftJoin('special_prices', function($join) use($t)
                                {
                                    $join->on('special_prices.customer_id','=',DB::raw("'".$t['customer']['id']."'"));
                                    $join->on('special_prices.product_id', '=', 'products.id');
                                    $join->on('special_prices.status', '=', DB::raw("'1'"));
                                })
                            ->where('products.status','1')
                            ->select('products.id','products.code','products.name',DB::raw('coalesce(special_prices.price,products.price) as "price"'))
                            ->get();
                        $task[$c]['customer']['groupcompany'] = DB::table('companies')
                            ->where('companies.group_id',explode(',',$t['customer']['group'])[0])
                            ->select('companies.*')
                            ->first() ?? null;
                    }
                }
            }else{
                $message = false;
            }
            $inventorybalance = InventoryBalance::where('lorry_id',$trip->lorry_id)->with('product')->get()->toarray();
            if($message){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.task_found',
                    'data' => [
                        'task' => $task,
                        'stock' => $inventorybalance
                    ]
                ], 200);
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.task_not_found',
                    'data' => [
                        'task' => null,
                        'stock' => $inventorybalance
                    ]
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
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

    public function getcustomer(Request $request){
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
            $customer = Customer::select('customers.*', 'assigns.sequence')
                ->join('assigns', 'customers.id', '=', 'assigns.customer_id')
                ->where('assigns.driver_id', $driver->id)
                ->orderBy('assigns.sequence', 'asc')
                ->get();          
            //process
            if($customer != null){
                return response()->json([
                    'result' => true,
                    'message' => __LINE__.$this->message_separator.'api.message.customer_found',
                    'data' => $customer
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
            //validation
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
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
            $invoicepayment->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$invoicepayment->customer_id.');')[0]->credit,2);
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
                ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
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
            $query = Invoice::where('driver_id', $driver->id);

            // Apply customer filter if customer_id is provided
            if ($customer_id) {
                $query->where('customer_id', $customer_id);
            }

            // Get sales invoices
            $invoices = $query->with(['customer:id,company,phone,paymentterm', 'invoicedetail.product:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Get driver's current trip ID
            $driverTripId = $driver->trip_id ?? null;

            // Define cancellable statuses
            $cancellableStatuses = [Invoice::STATUS_COMPLETED];

            // Format the response
            $formattedInvoices = $invoices->map(function($invoice) use ($driverTripId, $cancellableStatuses) {
                
                // Check if this specific invoice can be cancelled
                $allowCancel = true;
                
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
                    'remark' => $invoice->remark,
                    'total' => number_format($invoice->total, 2),
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
                            'total_formatted' => number_format($detail->totalprice, 2)
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
                    'total' => number_format($invoice->total, 2),
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
                            'total_formatted' => number_format($detail->totalprice, 2)
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
            
            $min = 450;
            $each = 23;
            $height = (count($invoice['invoicedetail']) * $each) + $min;
            $creditData = $this->calculateCustomerCredit(
                $invoice->customer_id, 
                $invoice->updated_at
            );
            
            $invoice->newcredit = round($creditData['credit'] ?? 0, 2);

            $invoice->customer->groupcompany = DB::table('companies')
            ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
            ->select('companies.*')
            ->first() ?? null;       


            $pdf = Pdf::loadView('invoices.print', [
                'invoice' => $invoice
            ]);
            
            $pdf->setPaper(array(0, 0, 300, $height), 'portrait')
                ->setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true]);

            return base64_encode($pdf->output());
            
        } catch (Exception $e) {
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
            
            // Validate trip
            $trip = Trip::where('driver_id', $driver->id)->orderby('date', 'desc')->first();
            if (!empty($trip)) {
                if ($trip->type == 2) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                        'data' => null
                    ], 401);
                }
            } else {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'api.message.trip_had_not_started',
                    'data' => null
                ], 401);
            }
            
            // Validate request
            $validator = Validator::make($request->all(), [
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
            
            // Validate product batches and check quantities
            foreach ($data['invoicedetail'] as $item) {
                $productBatch = ProductBatch::where('id', $item['product_batch_id'])
                    ->where('product_id', $item['product_id'])
                    ->first();
                
                if (empty($productBatch)) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Invalid product batch for product ID: ' . $item['product_id'],
                        'data' => null
                    ], 400);
                }
                
                if ($productBatch->quantity < $item['quantity']) {
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__ . $this->message_separator . 'Insufficient batch quantity for product batch: ' . $productBatch->batch_code . '. Available: ' . $productBatch->quantity . ', Requested: ' . $item['quantity'],
                        'data' => null
                    ], 400);
                }
            }
            
            // Process invoice
            $runningno = Code::where('code', 'invoicerunningnumber')->first();
            $runningno->value = intval($runningno->value) + 1;
            $runningno->save();
            
            DB::beginTransaction();
            
            // Generate invoice number
            $invoiceno = "INV" . str_pad($runningno->value, 7, '0', STR_PAD_LEFT);
            
            // Create invoice
            $invoice = new Invoice();
            $invoice->date = $data['date'] ?? date('Y-m-d H:i:s');
            $invoice->invoiceno = $invoiceno;
            $invoice->customer_id = $data['customer_id'];
            $invoice->driver_id = $trip->driver_id;
            $invoice->kelindan_id = $trip->kelindan_id;
            $invoice->agent_id = $customer->agent_id;
            $invoice->supervisor_id = $customer->supervisor_id;
            $invoice->paymentterm = $data['paymentterm'];
            $invoice->status = 1;
            $invoice->chequeno = $data['cheque_no'] ?? null;
            $invoice->remark = $data['remark'] ?? null;
            $invoice->save();
            
            $totalprice = 0;
            
            // Process invoice details
            foreach ($data['invoicedetail'] as $item) {
                $productBatch = ProductBatch::find($item['product_batch_id']);
                
                // Create invoice detail
                $invoicedetail = new InvoiceDetail();
                $invoicedetail->invoice_id = $invoice->id;
                $invoicedetail->product_id = $item['product_id'];
                $invoicedetail->product_batch_id = $item['product_batch_id'];
                $invoicedetail->quantity = $item['quantity'];
                $invoicedetail->price = $item['price'];
                $invoicedetail->totalprice = $item['quantity'] * $item['price'];
                $invoicedetail->remark = $item['remark'] ?? null;
                $invoicedetail->save();
                
                $totalprice += $invoicedetail->totalprice;
                
                // Deduct quantity from product batch
                $oldQuantity = $productBatch->quantity;
                $productBatch->quantity = $oldQuantity - $item['quantity'];
                $productBatch->save();
                
                // Create inventory transaction record
                $inventoryTransaction = new InventoryTransaction();
                $inventoryTransaction->type = 2;
                $inventoryTransaction->product_id = $item['product_id'];
                $inventoryTransaction->batch_id = $item['product_batch_id'];
                $inventoryTransaction->quantity = -$item['quantity'];
                $inventoryTransaction->date = now();
                $inventoryTransaction->user = $driver->employeeid . " (" . $driver->name . ")";
                $inventoryTransaction->remark = 'Invoice #' . $invoice->invoiceno . ' - Sale of product';
                $inventoryTransaction->save();
                
                // Update lorry inventory balance (keeping your existing logic)
                $inventorybalance = InventoryBalance::where('lorry_id', $trip->lorry_id)->first();

                $inventorybalance->updateBatchQuantity(
                    $item['product_batch_id'], 
                    $item['quantity'], 
                    'subtract'
                );
            }
            
            // Create invoice payment for cash transactions
            if ($data['paymentterm'] == 1) {
                $invoicepayment = new InvoicePayment();
                $invoicepayment->invoice_id = $invoice->id;
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
            
            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'api.message.invoice_add_successfully',
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
    
    private function calculateCustomerCredit($customerId, $asOfDate)
    {
        try {
            // Get total invoiced amount (what the customer owes)
            $totalInvoiced = Invoice::where('invoices.customer_id', $customerId)
                ->where('invoices.status', 1)
                ->where('invoices.updated_at', '<=', $asOfDate)
                ->join('invoice_details', 'invoices.id', '=', 'invoice_details.invoice_id')
                ->selectRaw('COALESCE(SUM(invoice_details.totalprice), 0) as total')
                ->value('total'); 

            // Get total paid amount (what the customer has paid)
            $totalPaid = InvoicePayment::where('customer_id', $customerId)
                ->where('status', 1)
                ->where('approve_at', '<=', $asOfDate)
                ->sum('amount') ?? 0;

            $outstandingBalance = $totalInvoiced - $totalPaid;

            return [
                'totalprice' => $totalInvoiced,
                'paid' => $totalPaid,
                'credit' => $outstandingBalance
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
    
            if (empty($invoice)) {
                abort('404');
            }
    
            $min = 450;
            $each = 23;
            $height = (count($invoice['invoicedetail']) * $each) + $min;
    
            try
            {
                $credit = $this->calculateCustomerCredit($invoice->customer_id, $invoice->updated_at);

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
            ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
            ->select('companies.*')
            ->first() ?? null;
            
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
	

     public function addpayment(Request $request){
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
            //validation
            
            $validator = Validator::make($request->all(), [
                'date' => 'date_format:Y-m-d H:i:s',
                'customer_id' => 'required|numeric',
                'type' => 'required|numeric|gt:0|lt:6',
                'remark' => 'present|nullable|string',
                'amount' =>'required|numeric',
                
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.$validator->errors()->first(),
                    'data' => null,
                ], 400);
            }
            $customer = Customer::where('id',$data['customer_id'])->first();
            if(empty($customer)){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.invalid_customer',
                    'data' => null,
                ], 400);
            }
            //process
            
            DB::beginTransaction();
            
            $invoicepayment = New InvoicePayment();
            if(isset($data['invoice_id'])){
                $invoicepayment->invoice_id = $data['invoice_id'];
            }
            
            $invoicepayment->type = $data['type'];
            $invoicepayment->customer_id = $data['customer_id'];
            $invoicepayment->amount = $data['amount'];
            $invoicepayment->status = 1;
            $invoicepayment->chequeno = $data['cheque_no'];
            $invoicepayment->driver_id = $driver->id;
            $invoicepayment->approve_by = $driver->name;
            $invoicepayment->approve_at = date('Y-m-d H:i:s');
            //$invoicepayment->created_at = $data['date'];
            $invoicepayment->save();
            
            DB::commit();
            $iv = InvoicePayment::where('id',$invoicepayment->id)->get()->first();
           
            $iv['payment_no'] = sprintf('PR%05d',$iv->id);
            
            
             try
            {
                $credit = DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');');
                
                if($credit)
                {
                    $iv->newcredit = round($credit[0]->credit,2);
    
                }
    
            }
            catch(Exception $ex)
            {
                 $iv->newcredit  = 0;
            }
            
           
           // $iv->newcredit = round(DB::select('call ice_spGetCustomerCreditByDate("'.date('Y-m-d H:i:s').'",'.$iv->customer_id.');')[0]->credit,2);
           
            return response()->json([
                'result' => true,
                'message' => __LINE__.$this->message_separator.'api.message.invoice_add_successfully',
                'data' => $iv
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
            ->where('companies.group_id',explode(',',$invoice->customer->group)[0])
            ->select('companies.*')
            ->first() ?? null;
            
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            //if(!empty($trip)){
            //    if($trip->type == 2){
            //        return response()->json([
            //            'result' => false,
            //            'message' => __LINE__.$this->message_separator.'Trip had not started',
            //            'data' => null
            //        ], 401);
            //    }
            //}else{
            //    return response()->json([
            //        'result' => false,
            //        'message' => __LINE__.$this->message_separator.'Trip had not started',
            //        'data' => null
            //    ], 401);
            //}
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $drivers = Trip::where('driver_id','!=',$trip->driver_id)
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
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
        $totrip = Trip::where('driver_id', $data['driver_id'])->orderby('date','desc')->first();
        if(!empty($totrip)){
            if($totrip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.selected_driver_trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $request = InventoryTransfer::where('from_driver_id', $trip->driver_id)
            ->where('date', '>=', date('Y-m-d 00:00:00'))
            ->with('product:id,name')
            ->with('todriver:id,name')
            ->orderby('date','desc')
            ->get(['id','date','status','quantity','product_id','to_driver_id'])
            ->toarray();
            $pending = InventoryTransfer::where('to_driver_id', $trip->driver_id)
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
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
        if(empty($fromdriver)){
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
                   'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.transfer_rejecet_successfully',
                    'data' => null
                ], 200);
            }
            if($data['status'] == 2){
                $inventorytransfer->status = 2;
                $inventorytransfer->save();
                 //from
                 $frominventorybalance = Inventorybalance::where('lorry_id',$inventorytransfer->from_lorry_id)
                 ->where('product_id',$inventorytransfer->product_id)->first();
                 if(empty($frominventorybalance)){
                     $newfrominventorybalance = New Inventorybalance();
                     $newfrominventorybalance->lorry_id = $inventorytransfer->from_lorry_id;
                     $newfrominventorybalance->product_id = $inventorytransfer->product_id;
                     $newfrominventorybalance->quantity = 0 - $inventorytransfer->quantity;
                     $newfrominventorybalance->save();
                 }else{
                     $frominventorybalance->quantity = $frominventorybalance->quantity - $inventorytransfer->quantity;
                     $frominventorybalance->save();
                 }
                 $frominventorytransaction = New InventoryTransaction();
                 $frominventorytransaction->lorry_id = $inventorytransfer->from_lorry_id;
                 $frominventorytransaction->product_id = $inventorytransfer->product_id;
                 $frominventorytransaction->quantity = $inventorytransfer->quantity * -1;
                 $frominventorytransaction->type = 4;
                 $frominventorytransaction->user = $fromdriver->employeeid . " (".$fromdriver->name.") => " . $todriver->employeeid . " (".$todriver->name.")";
                 $frominventorytransaction->date = date('Y-m-d H:i:s');
                 $frominventorytransaction->save();
                 //to
                 $toinventorybalance = Inventorybalance::where('lorry_id',$inventorytransfer->to_lorry_id)
                 ->where('product_id',$inventorytransfer->product_id)->first();
                 if(empty($toinventorybalance)){
                     $newtoinventorybalance = New Inventorybalance();
                     $newtoinventorybalance->lorry_id = $inventorytransfer->to_lorry_id;
                     $newtoinventorybalance->product_id = $inventorytransfer->product_id;
                     $newtoinventorybalance->quantity = $inventorytransfer->quantity;
                     $newtoinventorybalance->save();
                 }else{
                     $toinventorybalance->quantity = $toinventorybalance->quantity + $inventorytransfer->quantity;
                     $toinventorybalance->save();
                 }
                 $toinventorytransaction = New InventoryTransaction();
                 $toinventorytransaction->lorry_id = $inventorytransfer->to_lorry_id;
                 $toinventorytransaction->product_id = $inventorytransfer->product_id;
                 $toinventorytransaction->quantity = $inventorytransfer->quantity;
                 $toinventorytransaction->type = 4;
                 $toinventorytransaction->user = $fromdriver->employeeid . " (".$fromdriver->name.") => " . $todriver->employeeid . " (".$todriver->name.")";
                 $toinventorytransaction->date = date('Y-m-d H:i:s');
                 $toinventorytransaction->save();
                 DB::commit();
                 return response()->json([
                    'result' => false,
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
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
            $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
            if(!empty($trip)){
                if($trip->type == 2){
                    return response()->json([
                        'result' => false,
                        'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                        'data' => null
                    ], 400);
                }
            }else{
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
            //process
            $driver = Driver::where('id','!=',$trip->driver_id)->get()->toarray();
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
            }
        }else{
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json(['result' => false, 'message' => 'Trip had not started', 'data' => null], 400);
            }
        }else{
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
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
        $trip = Trip::where('driver_id', $driver->id)->orderby('date','desc')->first();
        if(!empty($trip)){
            if($trip->type == 2){
                return response()->json([
                    'result' => false,
                    'message' => __LINE__.$this->message_separator.'api.message.trip_had_not_started',
                    'data' => null
                ], 400);
            }
        }else{
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
            $bank_in = DB::Select('select coalesce(sum(coalesce(bank_in,0)),0) as bank_in from trips where type = 2 and driver_id = '.$driver->id.' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->bank_in;
            $cash_left = DB::Select('select coalesce(sum(coalesce(cash,0)),0) as cash from trips where type = 2 and driver_id = '.$driver->id.' and created_at >= "'.$data['date'].'" and created_at < "'.date('Y-m-d', strtotime("+1 day", strtotime($data['date']))).'";')[0]->cash;
            // $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id,sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id left join invoice_payments ip on ip.invoice_id = i.id where i.status = 1 and i.date = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and ip.id is null group by i.id ) a')[0]->credit;
            $credit = DB::select('select sum(a.totalprice) as credit from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 2 group by i.id ) a')[0]->credit;
            $bank = DB::select('select sum(a.totalprice) as bank from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 3 group by i.id ) a')[0]->bank;
            $tng = DB::select('select sum(a.totalprice) as tng from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 4 group by i.id ) a')[0]->tng;
            $cheque = DB::select('select sum(a.totalprice) as cheque from ( select i.id, sum(id.totalprice) as totalprice from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' and i.paymentterm = 5 group by i.id ) a')[0]->cheque;
            $productsold = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice > 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id)[0]->productsold;
            $solddetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id  left join products p on p.id = id.product_id where i.status = 1 and id.totalprice > 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $productfoc = DB::Select('select sum(id.quantity) as productsold from invoices i left join invoice_details id on id.invoice_id = i.id where i.status = 1 and id.totalprice = 0 and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id)[0]->productsold;
            $focdetail = DB::select('select p.name, sum(id.quantity) as quantity, sum(id.totalprice) as price from invoices i left join invoice_details id on id.invoice_id = i.id left join products p on p.id = id.product_id where i.status = 1 and id.totalprice = 0  and DATE(i.date) = "'.$data['date'].'" and i.driver_id = '.$driver->id.' group by id.product_id, p.id, p.name');
            $trip = DB::table('trips as t')
                ->select([
                    't.id',
                    't.advance_amount',  // Make sure this matches your column name exactly
                    'd.name as driver_name',
                    'k.name as kelindan_name', 
                    'l.lorryno'
                ])
                ->leftJoin('drivers as d', 'd.id', '=', 't.driver_id')
                ->leftJoin('kelindans as k', 'k.id', '=', 't.kelindan_id')
                ->leftJoin('lorrys as l', 'l.id', '=', 't.lorry_id')
                ->where('t.driver_id', $driver->id)
                ->where('t.type', 1)
                ->whereDate('t.date', $data['date'])  // Better date filtering
                ->get()
                ->map(function ($trip) {
                    // Convert null advance_amount to 0 if needed
                    $trip->advance_amount = $trip->advance_amount ?? 0;
                    return $trip;
                });                        
            $transaction = DB::table('inventory_transactions as i_t')
            ->join('products as p', 'p.id', '=', 'i_t.product_id')
            ->join('drivers as d', function($join) use ($driver) {
                $join->where('d.id', '=', $driver->id)
                    ->where(DB::raw("SUBSTRING_INDEX(i_t.user, ' ', 1)"), '=', DB::raw('d.employeeid'))
                    ->where(DB::raw("REPLACE(SUBSTRING_INDEX(SUBSTRING_INDEX(i_t.user, '(', -1), ')', 1), ')', '')"), '=', DB::raw('d.name'));
            })
            ->where('i_t.type', 5)
            ->where('i_t.created_at', '>=', $data['date'] . ' 00:00:00')
            ->where('i_t.created_at', '<', $data['date'] . ' 23:59:59')
            ->select('p.name', 'i_t.quantity')
            ->get();

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
                'cash_left' =>  ceil($cash_left),
                'bank_in' => round($bank_in,2),
                'wastage' => $transaction,
                'credit' => round($credit,2),
                'onlinebank' =>round($bank,2),
                'tng' =>round($tng,2),
                'cheque' =>round($cheque,2),
                'productsold' => [
                    'total_quantity' =>round($productsold,2),
                    'details' =>$solddetail
                ],
                'productfoc' => [
                    'total_quantity' =>round($productfoc,2),
                    'details' =>$focdetail
                ],
                'trip' => $trip
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

            // Get the latest trip for this driver
            $latestTrip = Trip::where('driver_id', $driver->id)
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
                        'price' => number_format($price, 2),
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

            // Get the latest trip for this driver
            $latestTrip = Trip::where('driver_id', $driver->id)
                ->where('type', 1)
                ->orderBy('date', 'desc')
                ->first();

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
        
        $latestTrip = Trip::where('driver_id', $driver->id)
                        ->where('type', 1)
                        ->orderBy('date', 'desc')
                        ->first();
                        
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

        // Check if there's already a pending inventory count for this driver
        $inventoryCount = InventoryCount::where('driver_id', $driver->id)
            ->where('status','!=', InventoryCount::STATUS_APPROVED)
            ->first();

        if($inventoryCount){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'You have request for Stock Count, please Contact your Stock Manager to approved.',
                'data' => null
            ], 200);
        }

        // Get driver's latest trip
        $latestTrip = Trip::where('driver_id', $driver->id)
            ->where('type', 1)
            ->orderBy('date', 'desc')
            ->first();

        if (!$latestTrip) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver start trip record not found.',
                'data' => null
            ], 200);
        }

        $lorryId = $latestTrip->lorry_id;

        try {
            // Get inventory balance for this lorry
            $inventoryBalance = InventoryBalance::where('lorry_id', $lorryId)->first();

            if (!$inventoryBalance || empty($inventoryBalance->batches)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No inventory found for your lorry.',
                    'data' => null
                ], 200);
            }

            // Get all batch IDs from the inventory
            $batchIds = array_keys($inventoryBalance->batches);

            // Fetch batch details with product information
            $batches = ProductBatch::with('product')
                ->whereIn('id', $batchIds)
                ->where('quantity', '>', 0) // Only batches with stock
                ->where('status', ProductBatch::STATUS_ACTIVE) // Only active batches
                ->get();

            // Prepare formatted items for inventory count
            $formattedItems = [];
            
            // Group by product
            $productGroups = [];
            
            foreach ($batches as $batch) {
                $productId = $batch->product_id;
                $availableQty = $inventoryBalance->batches[$batch->id] ?? 0;
                
                // Skip if no quantity available
                if ($availableQty <= 0) {
                    continue;
                }
                
                // Initialize product group if not exists
                if (!isset($productGroups[$productId])) {
                    $productGroups[$productId] = [
                        'product_id' => $productId,
                        'product_name' => $batch->product->name,
                        'current_quantity' => 0,
                        'batches' => []
                    ];
                }
                
                // Add batch details with counted_quantity initially set to null
                $productGroups[$productId]['batches'][] = [
                    'batch_id' => $batch->id,
                    'batch_code' => $batch->batch_code,
                    'current_quantity' => $availableQty,
                    'counted_quantity' => null, // Driver will update this later
                    'expiry_date' => $batch->expiry_date,
                    'formatted_expiry_date' => $batch->formatted_expiry_date,
                    'is_expiring_soon' => $batch->isExpiringSoon(),
                    'days_to_expiry' => $batch->days_to_expiry
                ];
                
                // Add to total product quantity
                $productGroups[$productId]['current_quantity'] += $availableQty;
            }

            // Convert product groups to array
            $formattedItems = array_values($productGroups);

            // If no items found, return error
            if (empty($formattedItems)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No active inventory items found in your lorry.',
                    'data' => null
                ], 200);
            }

            // Create inventory count record
            $inventoryCount = InventoryCount::create([
                'driver_id' => $driver->id,
                'trip_id' => $latestTrip->id,
                'items' => $formattedItems,
                'status' => InventoryCount::STATUS_PENDING,
                'remarks' => 'Auto-generated stock count request from driver app', // Optional default remark
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
                    'items' => $formattedItems // Include the items so driver can see what needs to be counted
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
            $inventoryCount = InventoryCount::where('driver_id', $driver->id)->first();

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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock count status: ' . $e->getMessage()
            ], 500);
        }
    }






    //Manager mobile side API
    public function getDriverProduct(Request $request)
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
            // Get all drivers
            $drivers = Driver::where('status', 1) // Assuming you have a status field
                ->orderBy('name')
                ->get(['id', 'name']); // Select necessary fields
            
            if ($drivers->isEmpty()) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 'No drivers found',
                    'data' => null
                ], 200);
            }

            // Get inventory balances for all drivers
            $allDriverInventory = InventoryBalance::whereIn('driver_id', $drivers->pluck('id'))
                ->get()
                ->groupBy('driver_id')
                ->map(function($inventories) {
                    return $inventories->pluck('quantity', 'product_id')->toArray();
                })
                ->toArray();

            // Get all products with categories
            $categories = ProductCategory::with(['products' => function($query) {
                $query->select('id', 'name', 'category_id', 'price', 'status')
                    ->where('status', 1)
                    ->orderBy('name');
            }])
            ->where('status', 1)
            ->orderBy('name')
            ->get();

            // Format the response for all drivers
            $output = $drivers->map(function($driver) use ($categories, $allDriverInventory) {
                $driverInventory = $allDriverInventory[$driver->id] ?? [];
                
                $driverProducts = $categories->map(function($category) use ($driverInventory) {
                    return [
                        'category_id' => $category->id,
                        'category_name' => $category->name,
                        'products' => $category->products->map(function($product) use ($driverInventory) {
                            // Get quantity from driver's inventory, default to 0 if not found
                            $quantity = $driverInventory[$product->id] ?? 0;
                            
                            return [
                                'id' => $product->id,
                                'name' => $product->name,
                                'price' => $product->price,
                                'quantity' => $quantity,
                                'status' => $product->getStatusTextAttribute()
                            ];
                        })
                    ];
                });

                return [
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                    'products' => $driverProducts
                ];
            });

            return response()->json([
                'result' => true,
                'message' => __LINE__ . $this->message_separator . 'Products for all drivers retrieved successfully',
                'data' => $output
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Error getting driver products: ' . $e->getMessage(),
                'data' => null
            ], 200);
        }
    }

    public function getStockCount(Request $request)
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
        
        $inventoryCounts = InventoryCount::all();
        
        // Get all unique product IDs from all inventory counts
        $allProductIds = [];
        foreach ($inventoryCounts as $count) {
            $items = $count->items;
            if (is_array($items)) {
                foreach ($items as $item) {
                    if (isset($item['product_id'])) {
                        $allProductIds[] = $item['product_id'];
                    }
                }
            }
        }
        
        // Fetch all products in one query
        $allProductIds = array_unique($allProductIds);
        $products = Product::whereIn('id', $allProductIds)
            ->get()
            ->keyBy('id');
        
        // Format the response
        $formattedCounts = $inventoryCounts->map(function ($count) use ($products) {
            $items = $count->items;
            $formattedItems = [];
            
            if (is_array($items)) {
                $formattedItems = array_map(function ($item) use ($products) {
                    $productId = $item['product_id'];
                    $product = $products[$productId] ?? null;
                    
                    return [
                        'product_id' => $item['product_id'],
                        'product_name' => $product ? $product->name : null,
                        'product_code' => $product ? $product->code : null, // Add other product fields if needed
                        'counted_quantity' => $item['counted_quantity'],
                        'current_quantity' => $item['current_quantity']
                    ];
                }, $items);
            }
            
            // Include driver info if you have driver relationship
            $driver = null;
            if ($count->driver_id) {
                $driver = Driver::find($count->driver_id);
            }
            
            return [
                'id' => $count->id,
                'driver_id' => $count->driver_id,
                'driver_name' => $driver ? $driver->name : null,
                'items' => $formattedItems,
                'status' => $count->status,
                'remarks' => $count->remarks,
                'rejection_reason' => $count->rejection_reason,
                'approved_by' => $count->approved_by,
                'trip_id' => $count->trip_id,
                'rejected_by' => $count->rejected_by,
                'approved_at' => $count->approved_at,
                'rejected_at' => $count->rejected_at,
                'created_at' => $count->created_at,
                'updated_at' => $count->updated_at
            ];
        });

        return response()->json([
            'result' => true,
            'message' => __LINE__ . $this->message_separator . 'Stock Count list retrieved successfully',
            'data' => $formattedCounts
        ], 200);
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

        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:inventory_counts,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.counted_quantity' => 'required|numeric|min:0',
            'items.*.current_quantity' => 'required|numeric|min:0',
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

        $inventoryCount = InventoryCount::find($id);

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

        try{
            // Validate that all items have counted_quantity filled
            $missingCountedItems = [];
            $formattedItems = [];
            
            foreach ($data['items'] as $item) {
                // Check if counted_quantity is provided and valid
                if (!isset($item['counted_quantity']) || 
                    (empty($item['counted_quantity']) && $item['counted_quantity'] !== '0' && $item['counted_quantity'] !== 0)) {
                    $product = Product::find($item['product_id']);
                    $missingCountedItems[] = $product ? $product->name : 'Product ID: ' . $item['product_id'];
                }
                
                // Format the item with proper data types
                $formattedItems[] = [
                    'product_id' => (string) $item['product_id'],
                    'counted_quantity' => (string) $item['counted_quantity'],
                    'current_quantity' => (int) $item['current_quantity']
                ];
            }
            
            // If there are items missing counted_quantity, return error
            if (!empty($missingCountedItems)) {
                return response()->json([
                    'result' => false,
                    'message' => __LINE__ . $this->message_separator . 
                        'Please provide counted quantity for: ' . implode(', ', $missingCountedItems),
                    'data' => null
                ], 200);
            }

            // Update the inventory count with new items and remarks
            $updateData = [
                'items' => $formattedItems,
                'status' => InventoryCount::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ];

            // Add remarks if provided
            if (!empty($data['remarks'])) {
                $updateData['remarks'] = $data['remarks'];
            }

            $inventoryCount->update($updateData);

            return response()->json([
                'result' => true,
                'message' => '' . __LINE__ . $this->message_separator . 'Stock Count approved successfully',
                'data' => $inventoryCount
            ], 200);

        }catch (\Exception $e){

            return response()->json([
                'result' => false,
                'message' => '' . __LINE__ . $this->message_separator . 'Stock Count Failed approved',
                'data' =>null
            ], 200);
        }
        


    }

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
            $user = User::where('email', $data['employeeid'])->first();

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



    
    public function getStockReturn(Request $request)
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

        if($driver->trip_id == NULL){
            return response()->json([
                'result' => false,
                'message' => __LINE__ . $this->message_separator . 'Driver have to start trip before perform any Action',
                'data' => null
            ], 200);
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

    









}
