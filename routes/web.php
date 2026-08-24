<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Agent;
use App\Models\Driver;
use App\Models\Product;
use App\Models\Code;
use App\Models\SpecialPrice;
use Rap2hpoutre\FastExcel\FastExcel;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::view('/privacy-policy','privacy');

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/test', function() {
// 	$role = User::where('id', 1)->firstOrFail();
//     $role->assignRole('admin');
// });


// Route::get('/import', function () {
//     DB::beginTransaction();

//     $now = now();
//     $collection = (new FastExcel)->import('public/Customer.xlsx');

//     for ($i = 0; $i < count($collection); $i++) {
//         $x = strtolower($collection[$i]['Payment Term']);
//         if ($x == 'cash') {
//             $term = 1;
//         } else if ($x == 'credit') {
//             $term = 2;
//         } else if ($x == 'online bankin') {
//             $term = 3;
//         } else if ($x == 'e-wallet') {
//             $term = 4;
//         } else if ($x == 'cheque') {
//             $term = 5;
//         }
        
//         $group_id = Code::where('code', 'customer_group')->where(DB::raw('BINARY `description`'), $collection[$i]['Group'])->value('value');

//         if (!isset($term) || $group_id == null) {
//             dd($collection[$i], $group_id);
//         }
        
//         $customer = Customer::create([
//             'code' => $collection[$i]['Code'],
//             'company' => $collection[$i]['Company'],
//             'chinese_name' => null,
//             'paymentterm' => $term,
//             'group' => $group_id,
//             'agent_id' => null,
//             'phone' => $collection[$i]['Phone'],
//             'address' => $collection[$i]['Address'],
//             'tin' => null,
//             'sst' => null,
//             'status' => $collection[$i]['Status'] == 'Active' ? 1 : 0,
//             'created_at' => $now,
//             'updated_at' => $now,
//         ]);
        
//         for ($j = 1; $j < 3; $j++) {
//             if ($collection[$i]['Product Name ' . $j] == '') {
//                 continue;
//             }
            
//             $prod_name = $collection[$i]['Product Name ' . $j];
//             if ($prod_name == 'Ice 1 ') {
//                 $prod_name = 'Ice 1';
//             }
//             $prod_name = str_replace(' ', '0', $prod_name);
            
//             $product_id = Product::where(DB::raw('BINARY `code`'), $prod_name)->value('id');
            
//             if ($product_id == null) {
//                 dd($product_id, $prod_name, $collection[$i]);
//             }
            
//             SpecialPrice::create([
//                 'product_id' => $product_id,
//                 'customer_id' => $customer->id,
//                 'price' => $collection[$i]['RM ' . $j],
//                 'status' => 1,
//             ]);
//         }
//     }
    
//     DB::commit();

//     dd('end');
// });

// Route::get('/assign', function () {
//     DB::beginTransaction();

//     $now = now();

//     $drivers = Driver::get();
//     $data = [];
//     $ids = [];
//     $group_ids = [];
//     for ($i = 0; $i < count($drivers) ;$i++) {
//         $id = str_replace('JK_', '',$drivers[$i]->employeeid);
//         $ids[] = $id;
//         $group_id = Code::where('code', 'customer_group')->where(DB::raw('BINARY `description`'), 'Kedai ' . $id)->value('value');
//         $group_ids[] = $group_id;
//         $cus = Customer::get();

//         for ($j = 0; $j < count($cus) ;$j++) {
//             $cus_group_ids = explode(',', $cus[$j]->group);
            
//             if (in_array($group_id, $cus_group_ids) == true) {
//                 DB::table('assigns')->insert([
//                     'driver_id' => $drivers[$i]->id,
//                     'customer_id' => $cus[$j]->id,
//                     'sequence' => $j,
//                     'created_at' => $now,
//                     'updated_at' => $now,
//                 ]);
//             }
//         }
//     }
//     // DB::table('assigns')->where('id', '>', 768)->delete();
    
//     DB::commit();

//     dd('end');
// });

Auth::routes();

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:cache');
    Artisan::call('view:clear');
    Artisan::call('route:clear');
    return 'Application cache has been cleared';
});

// Route::get('/archived', [App\Http\Controllers\ArcHomeController::class, 'index'])->name('home');

// Route::get('/scheduler/updatedo', [App\Http\Controllers\scheduler::class, 'updateDoRate']);
// Route::get('/scheduler/archiveddo', [App\Http\Controllers\scheduler::class, 'archivedDeliveryOrder']);
Route::get('/scheduler/checklorryservice', [App\Http\Controllers\scheduler::class, 'checkLorryService']);
Route::get('/scheduler/checkkelindanpermit', [App\Http\Controllers\scheduler::class, 'checkKelindanPermit']);

// Route::group(['middleware' => ['role:admin']], function() {
//     Route::resource('roles', App\Http\Controllers\RoleController::class);
//     Route::resource('codes', App\Http\Controllers\CodeController::class);
//     Route::resource('permissions', App\Http\Controllers\PermissionController::class);
// });


// Route::resource('codes', App\Http\Controllers\CodeController::class);
// Route::resource('users', UserController::class);
// Route::resource('userHasRoles', App\Http\Controllers\UserHasRoleController::class);
// Route::resource('roles', App\Http\Controllers\RoleController::class);
// Route::resource('roleHasPermissions', App\Http\Controllers\RoleHasPermissionController::class);
Route::group(['middleware' => ['auth']], function() {
    Route::get('/', [App\Http\Controllers\InvoiceController::class, 'index']);
    Route::get('/home', [App\Http\Controllers\InvoiceController::class, 'index'])->name('home');
    // Route::post('/home/getProductDelivered', [App\Http\Controllers\HomeController::class, 'getProductDelivered']);
    Route::post('/home/getTotalSales', [App\Http\Controllers\HomeController::class, 'getTotalSales']);
    Route::post('/home/getTotalSalesQty', [App\Http\Controllers\HomeController::class, 'getTotalSalesQty']);
    // Route::post('/home/getDriverPerformance', [App\Http\Controllers\HomeController::class, 'getDriverPerformance']);
    // Route::post('/home/getDriverList', [App\Http\Controllers\HomeController::class, 'getDriverList']);
    // Route::post('/home/getProductType', [App\Http\Controllers\HomeController::class, 'getProductType']);
    // Route::resource('saveviews', App\Http\Controllers\saveviewsController::class);
    // Route::post('/saveviews/massdestroy', [App\Http\Controllers\saveviewsController::class, 'massdestroy']);
    // Route::get('/saveviews/view/{id}', [App\Http\Controllers\saveviewsController::class, 'view'])->name('showview');
    // Route::group(['middleware' => ['permission:deliveryorder']], function() {
    //     Route::resource('deliveryOrders', App\Http\Controllers\DeliveryOrderController::class);
    //     Route::post('/prices/getBillingRate', [App\Http\Controllers\PriceController::class, 'getBillingRate']);
    //     Route::post('/deliveryOrders/getDriverInfo', [App\Http\Controllers\DeliveryOrderController::class, 'getDriverInfo']);
    //     Route::post('/deliveryOrders/getDriverLorry', [App\Http\Controllers\DeliveryOrderController::class, 'getDriverLorry']);
    //     Route::post('/deliveryOrders/getLorryInfo', [App\Http\Controllers\DeliveryOrderController::class, 'getLorryInfo']);
    //     Route::post('/deliveryOrders/getClaimInfo', [App\Http\Controllers\DeliveryOrderController::class, 'getClaimInfo']);
    //     Route::post('/deliveryOrders/getBillingRateInfo', [App\Http\Controllers\DeliveryOrderController::class, 'getBillingRateInfo']);
    //     Route::post('/deliveryOrders/getCommissionRateInfo', [App\Http\Controllers\DeliveryOrderController::class, 'getCommissionRateInfo']);
    //     Route::post('/deliveryOrders/getBillingRate', [App\Http\Controllers\DeliveryOrderController::class, 'getBillingRate']);
    //     Route::post('/deliveryOrders/getCommissionRate', [App\Http\Controllers\DeliveryOrderController::class, 'getCommissionRate']);
    //     Route::post('/items/getBillingRate', [App\Http\Controllers\ItemController::class, 'getBillingRate']);
    //     Route::post('/items/getCommissionRate', [App\Http\Controllers\ItemController::class, 'getCommissionRate']);
    //     Route::post('/deliveryOrders/massdestroy', [App\Http\Controllers\DeliveryOrderController::class, 'massdestroy']);
    //     Route::post('/deliveryOrders/massupdatestatus', [App\Http\Controllers\DeliveryOrderController::class, 'massupdatestatus']);
    //     Route::post('/deliveryOrders/masssave', [App\Http\Controllers\DeliveryOrderController::class, 'masssave']);
    //     //Archived DeliveryOrder//
    //     Route::get('/archived/deliveryOrders', [App\Http\Controllers\ArcDeliveryOrderController::class, 'index']);
    //     Route::get('/archived/deliveryOrders/index', [App\Http\Controllers\ArcDeliveryOrderController::class, 'index']);
    //     Route::get('/archived/deliveryOrders/{id}', [App\Http\Controllers\ArcDeliveryOrderController::class, 'show']);
    //     Route::post('/archived/deliveryOrders/getClaimInfo', [App\Http\Controllers\ArcDeliveryOrderController::class, 'getClaimInfo']);
    //     //Archived DeliveryOrder//
    // });
    // Route::group(['middleware' => ['permission:loan']], function() {
    //     Route::resource('loans', App\Http\Controllers\LoanController::class);
    //     Route::post('loans/{loan}/start', [App\Http\Controllers\LoanController::class, 'start'])->name('loans.start');
    //     Route::post('/loanPayment/getPaymentDetails', [App\Http\Controllers\LoanpaymentController::class, 'getPaymentDetails']);
    //     Route::post('/loans/masssave', [App\Http\Controllers\LoanController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:loanpayment']], function() {
    //     Route::resource('loanpayments', App\Http\Controllers\LoanpaymentController::class);
    //     Route::post('/loanpayments/massdestroy', [App\Http\Controllers\LoanpaymentController::class, 'massdestroy']);
    //     Route::post('/loanpayments/massupdatestatus', [App\Http\Controllers\LoanpaymentController::class, 'massupdatestatus']);
    //     Route::post('/loanpayments/masssave', [App\Http\Controllers\LoanpaymentController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:paymentdetail']], function() {
    //     Route::resource('paymentdetails', App\Http\Controllers\paymentdetailController::class);
    //     Route::post('/paymentdetails/massgenerate', [App\Http\Controllers\paymentdetailController::class, 'massgenerate']);
    //     Route::post('/paymentdetails/generate', [App\Http\Controllers\paymentdetailController::class, 'generate']);
    //     Route::post('/paymentdetails/getGenerateDetails', [App\Http\Controllers\paymentdetailController::class, 'getGenerateDetails']);
    //     Route::post('/paymentdetails/massdestroy', [App\Http\Controllers\paymentdetailController::class, 'massdestroy']);
    //     Route::post('/paymentdetails/masssave', [App\Http\Controllers\paymentdetailController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:compound']], function() {
    //     Route::resource('compounds', App\Http\Controllers\CompoundController::class);
    //     Route::post('/compounds/massdestroy', [App\Http\Controllers\CompoundController::class, 'massdestroy']);
    //     Route::post('/compounds/massupdatestatus', [App\Http\Controllers\CompoundController::class, 'massupdatestatus']);
    //     Route::post('/compounds/masssave', [App\Http\Controllers\CompoundController::class, 'masssave']);
    //     Route::post('/compounds/getLorryPermitHolder', [App\Http\Controllers\CompoundController::class, 'getLorryPermitHolder']);
    // });
    // Route::group(['middleware' => ['permission:advance']], function() {
    //     Route::resource('advances', App\Http\Controllers\AdvanceController::class);
    //     Route::post('/advances/massdestroy', [App\Http\Controllers\AdvanceController::class, 'massdestroy']);
    //     Route::post('/advances/massupdatestatus', [App\Http\Controllers\AdvanceController::class, 'massupdatestatus']);
    //     Route::post('/advances/masssave', [App\Http\Controllers\AdvanceController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:claim']], function() {
    //     Route::resource('claims', App\Http\Controllers\ClaimController::class);
    //     Route::post('/claims/getDOList', [App\Http\Controllers\ClaimController::class, 'getDOList']);
    //     Route::post('/claims/massdestroy', [App\Http\Controllers\ClaimController::class, 'massdestroy']);
    //     Route::post('/claims/massupdatestatus', [App\Http\Controllers\ClaimController::class, 'massupdatestatus']);
    //     Route::post('/claims/masssave', [App\Http\Controllers\ClaimController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:bonus']], function() {
    //     Route::resource('bonuses', App\Http\Controllers\BonusController::class);
    //     Route::post('/bonuses/massdestroy', [App\Http\Controllers\BonusController::class, 'massdestroy']);
    //     Route::post('/bonuses/massupdatestatus', [App\Http\Controllers\BonusController::class, 'massupdatestatus']);
    //     Route::post('/bonuses/masssave', [App\Http\Controllers\BonusController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:price']], function() {
    //     Route::resource('prices', App\Http\Controllers\PriceController::class);
    //     Route::post('/prices/massdestroy', [App\Http\Controllers\PriceController::class, 'massdestroy']);
    //     Route::post('/prices/massupdatestatus', [App\Http\Controllers\PriceController::class, 'massupdatestatus']);
    //     Route::post('/prices/masssave', [App\Http\Controllers\PriceController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:item']], function() {
    //     Route::resource('items', App\Http\Controllers\ItemController::class);
    //     Route::post('/items/massdestroy', [App\Http\Controllers\ItemController::class, 'massdestroy']);
    //     Route::post('/items/massupdatestatus', [App\Http\Controllers\ItemController::class, 'massupdatestatus']);
    //     Route::post('/items/masssave', [App\Http\Controllers\ItemController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:location']], function() {
    //     Route::resource('locations', App\Http\Controllers\LocationController::class);
    //     Route::post('/locations/massdestroy', [App\Http\Controllers\LocationController::class, 'massdestroy']);
    //     Route::post('/locations/massupdatestatus', [App\Http\Controllers\LocationController::class, 'massupdatestatus']);
    //     Route::post('/locations/masssave', [App\Http\Controllers\LocationController::class, 'masssave']);
    // });
    // Route::group(['middleware' => ['permission:vendor']], function() {
    //     Route::resource('vendors', App\Http\Controllers\VendorController::class);
    //     Route::post('/vendors/massdestroy', [App\Http\Controllers\VendorController::class, 'massdestroy']);
    //     Route::post('/vendors/massupdatestatus', [App\Http\Controllers\VendorController::class, 'massupdatestatus']);
    //     Route::post('/vendors/masssave', [App\Http\Controllers\VendorController::class, 'masssave']);
    //     Route::get('/reports/vendoroneview/{id}/{datefrom}/{dateto}', [App\Http\Controllers\ReportController::class, 'vendoroneview'])->name('vendoroneview');
    //     Route::get('/reports/getVendoroneviewPDF/{id}/{datefrom}/{dateto}/{function}', [App\Http\Controllers\ReportController::class, 'getVendoroneviewPDF'])->name('getVendoroneviewPDF');
    // });
    Route::group(['middleware' => ['permission:report']], function() {
        Route::resource('reports', App\Http\Controllers\ReportController::class);
        Route::post('/reports/run', [App\Http\Controllers\ReportController::class, 'run'])->name('reports.run');
        Route::get('/showreport/{id}', [App\Http\Controllers\ReportController::class, 'report'])->name('showreport');
        Route::get('/report/sellerinformationrecord', [App\Http\Controllers\ReportController::class, 'seller_information_record'])->name('seller_information_record');
        Route::get('/report/customerstatementofaccount', [App\Http\Controllers\ReportController::class, 'customer_statement_of_account'])->name('customer_statement_of_account');
        Route::get('/report/daily_sales_report_excel', [App\Http\Controllers\ReportController::class, 'daily_sales_report_excel'])->name('daily_sales_report_excel');
        Route::get('/stock-balance-report', [App\Http\Controllers\ReportController::class, 'generateStockBalanceReport'])->name('stock_balance_report_view');

        Route::get('/stock-received-report', [App\Http\Controllers\ReportController::class, 'stockReceivedReportView'])->name('stock_received_report_view');
        Route::post('/stock-received-report/generate', [App\Http\Controllers\ReportController::class, 'generateStockReceivedReport'])->name('stock_received_report_generate');

        Route::get('/daily-sales-report', [App\Http\Controllers\ReportController::class, 'dailySalesReportView'])->name('daily_sales_report_view');

        Route::get('/finished-goods-traceability', [App\Http\Controllers\ReportController::class, 'finishedGoodsTraceabilityView'])->name('finished_goods_traceability_view');

        Route::get('/stock-card-report', [App\Http\Controllers\ReportController::class, 'stockCardReportView'])->name('stock_card_report_view');
        Route::get('/get-product-batches', [App\Http\Controllers\ReportController::class, 'getProductBatches'])->name('reports.getProductBatches');
        Route::get('/stock-request-form-pdf', [App\Http\Controllers\ReportController::class, 'generateStockRequestFormPDF'])->name('stock_request_form_pdf');
    });


    // Route::group(['middleware' => ['permission:report|paymentdetail']], function() {
    //     Route::resource('reports', App\Http\Controllers\ReportController::class);
    //     Route::post('/reports/run', [App\Http\Controllers\ReportController::class, 'run']);
        // Route::resource('reportdetails', App\Http\Controllers\ReportdetailController::class);
    //     Route::get('/showreport/{id}', [App\Http\Controllers\ReportController::class, 'report'])->name('showreport');
    //     Route::get('/reports/paymentoneview/{id}', [App\Http\Controllers\ReportController::class, 'paymentoneview'])->name('paymentoneview');
    //     // Route::get('/reports/paymentoneviewtemplate/{id}', [App\Http\Controllers\ReportController::class, 'paymentoneviewtemplate'])->name('paymentoneviewtemplate');
    //     Route::get('/reports/getPaymentoneviewPDF/{id}/{function}', [App\Http\Controllers\ReportController::class, 'getPaymentoneviewPDF'])->name('getPaymentoneviewPDF');
    //     Route::get('/newreports', [App\Http\Controllers\ReportController::class, 'newreport'])->name('newreport');
    //     Route::get('/report/massDownloadPaymentoneviewPDF/{ids}', [App\Http\Controllers\ReportController::class, 'massDownloadPaymentoneviewPDF'])->name('massDownloadPaymentoneviewPDF');
    // });
    // Route::group(['middleware' => ['permission:commissionbyvendor']], function() {
    //     Route::resource('commissionByVendors', App\Http\Controllers\CommissionByVendorsController::class);
    //     Route::post('/commissionByVendors/massdestroy', [App\Http\Controllers\CommissionByVendorsController::class, 'massdestroy']);
    //     Route::post('/commissionByVendors/massupdatestatus', [App\Http\Controllers\CommissionByVendorsController::class, 'massupdatestatus']);
    //     Route::post('/commissionByVendors/masssave', [App\Http\Controllers\CommissionByVendorsController::class, 'masssave']);
    // });

    // Vans (lorries) write actions: full-access users only.
    Route::group(['middleware' => ['permission:lorry']], function() {
        Route::resource('lorries', App\Http\Controllers\LorryController::class)->except(['index', 'show']);
        Route::post('/lorries/massdestroy', [App\Http\Controllers\LorryController::class, 'massdestroy']);
        Route::post('/lorries/massupdatestatus', [App\Http\Controllers\LorryController::class, 'massupdatestatus']);
        Route::resource('servicedetails', App\Http\Controllers\servicedetailsController::class);
        Route::post('/servicedetails/massdestroy', [App\Http\Controllers\servicedetailsController::class, 'massdestroy']);
        Route::post('/servicedetails/getTyreServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getTyreServiceInfo']);
        Route::post('/servicedetails/getInsuranceServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getInsuranceServiceInfo']);
        Route::post('/servicedetails/getPermitServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getPermitServiceInfo']);
        Route::post('/servicedetails/getRoadtaxServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getRoadtaxServiceInfo']);
        Route::post('/servicedetails/getInspectionServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getInspectionServiceInfo']);
        Route::post('/servicedetails/getOtherServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getOtherServiceInfo']);
        Route::post('/servicedetails/getFireExtinguisherServiceInfo', [App\Http\Controllers\servicedetailsController::class, 'getFireExtinguisherServiceInfo']);
    });
    // Vans (lorries) read-only: full-access users OR inventory managers (view only).
    Route::group(['middleware' => ['role_or_permission:lorry|Inventory Admin']], function() {
        Route::get('/lorries', [App\Http\Controllers\LorryController::class, 'index'])->name('lorries.index');
        Route::get('/lorries/{lorry}', [App\Http\Controllers\LorryController::class, 'show'])->name('lorries.show');
    });
    Route::group(['middleware' => ['permission:driver']], function() {
        Route::get('/drivers/{id}/assign', [App\Http\Controllers\DriverController::class, 'assign'])->name('drivers.assign');
        Route::post('/drivers/{id}/addassign', [App\Http\Controllers\DriverController::class, 'addassign'])->name('drivers.addassign');
        Route::delete('/drivers/{id}/deleteassign', [App\Http\Controllers\DriverController::class, 'deleteassign'])->name('drivers.deleteassign');
        Route::resource('drivers', App\Http\Controllers\DriverController::class);
        Route::post('/drivers/massdestroy', [App\Http\Controllers\DriverController::class, 'massdestroy']);
        Route::post('/drivers/massupdatestatus', [App\Http\Controllers\DriverController::class, 'massupdatestatus']);
        Route::get('/driverLocations/getDriverSummary', [App\Http\Controllers\DriverLocationController::class, 'getDriverSummary'])->name('driverLocations.getDriverSummary');
        Route::resource('driverLocations', App\Http\Controllers\DriverLocationController::class);
    });
    Route::group(['middleware' => ['permission:kelindan']], function() {
        Route::resource('kelindans', App\Http\Controllers\KelindanController::class);
        Route::post('/kelindans/massdestroy', [App\Http\Controllers\KelindanController::class, 'massdestroy']);
        Route::post('/kelindans/massupdatestatus', [App\Http\Controllers\KelindanController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:agent']], function() {
        Route::resource('agents', App\Http\Controllers\AgentController::class);
        Route::post('/agents/getattachment', [App\Http\Controllers\AgentController::class, 'getattachment']);
        Route::post('/agents/addattachment', [App\Http\Controllers\AgentController::class, 'addattachment']);
        Route::post('/agents/massdestroy', [App\Http\Controllers\AgentController::class, 'massdestroy']);
        Route::post('/agents/massupdatestatus', [App\Http\Controllers\AgentController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:supervisor']], function() {
        Route::resource('supervisors', App\Http\Controllers\SupervisorController::class);
        Route::post('/supervisors/massdestroy', [App\Http\Controllers\SupervisorController::class, 'massdestroy']);
        Route::post('/supervisors/massupdatestatus', [App\Http\Controllers\SupervisorController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:product']], function() {
        Route::get('/products/sync-xero', [App\Http\Controllers\ProductController::class, 'syncXero']);
        Route::resource('products', App\Http\Controllers\ProductController::class);
        Route::post('/products/massdestroy', [App\Http\Controllers\ProductController::class, 'massdestroy']);
        Route::post('/products/massupdatestatus', [App\Http\Controllers\ProductController::class, 'massupdatestatus']);
        Route::get('/products/unit-codes', [App\Http\Controllers\ProductController::class, 'getUnitCodes'])->name('unit-codes.index');

    });

    Route::group(['middleware' => ['permission:product_batch']], function() {
        
        // Override resource parameter to use 'id' instead of 'productBatch'
        // Quantity changes still go through the stock-in/out/adjust actions
        // below, not edit/update - update() explicitly strips quantity.
        Route::resource('productBatches', App\Http\Controllers\ProductBatchController::class)->parameters([
            'productBatches' => 'id'
        ]);
        
        Route::post('/productBatches/stock-in/{id}', [App\Http\Controllers\ProductBatchController::class, 'stockIn'])->name('productBatches.stock-in');
        Route::post('/productBatches/stock-in-bulk', [App\Http\Controllers\ProductBatchController::class, 'stockInBulk'])->name('productBatches.stock-in-bulk');
        Route::post('/productBatches/stock-out/{id}', [App\Http\Controllers\ProductBatchController::class, 'stockOut'])->name('productBatches.stock-out');
        Route::post('/productBatches/adjust-stock/{id}', [App\Http\Controllers\ProductBatchController::class, 'adjustStock'])->name('productBatches.adjust-stock');
        // Mass actions
        Route::post('/productBatches/massdestroy', [App\Http\Controllers\ProductBatchController::class, 'massdestroy'])->name('productBatches.massdestroy');
        
        // Utility routes
        Route::get('/productBatches/check-expiry', [App\Http\Controllers\ProductBatchController::class, 'checkExpiry'])->name('productBatches.check-expiry');
        Route::get('/productBatches/{id}/print-label', [App\Http\Controllers\ProductBatchController::class, 'printLabel'])->name('productBatches.print-label');
        Route::get('/productBatches/by-product/{productId}', [App\Http\Controllers\ProductBatchController::class, 'getBatchesByProduct'])->name('productBatches.by-product');
        
        // Barcode generation routes
        Route::post('/productBatches/generate-barcode-preview', [App\Http\Controllers\ProductBatchController::class, 'generateBarcodePreview'])->name('productBatches.generate-barcode-preview');
        Route::get('/productBatches/download-barcode', [App\Http\Controllers\ProductBatchController::class, 'downloadBarcode'])->name('productBatches.download-barcode');
        Route::get('/productBatches/scan-barcode', [App\Http\Controllers\ProductBatchController::class, 'scanBarcode'])->name('productBatches.scan-barcode');
        Route::post('/productBatches/search-by-code/{batchCode?}', [App\Http\Controllers\ProductBatchController::class, 'searchByCode'])->name('productBatches.search-by-code');


    });
       Route::group(['middleware' => ['permission:stockrequest']], function() {
        // Index
        Route::get('/inventory-requests/{id}/with-batches', [App\Http\Controllers\InventoryRequestController::class, 'getRequestWithBatches'])->name('inventoryRequests.withBatches');
        Route::get('/inventoryRequests', [App\Http\Controllers\InventoryRequestController::class, 'index'])->name('inventoryRequests.index');
        // Store (Create)
        Route::post('/inventoryRequests/store', [App\Http\Controllers\InventoryRequestController::class, 'store'])->name('inventoryRequests.store');
        // Show
        Route::get('/inventoryRequests/show/{id}', [App\Http\Controllers\InventoryRequestController::class, 'show'])->name('inventoryRequests.show');
        // Update
        Route::put('/inventoryRequests/update/{id}', [App\Http\Controllers\InventoryRequestController::class, 'update'])->name('inventoryRequests.update');
        Route::post('/inventoryRequests/update/{id}', [App\Http\Controllers\InventoryRequestController::class, 'update']); // Alternative
        // Delete
        Route::delete('/inventoryRequests/delete/{id}', [App\Http\Controllers\InventoryRequestController::class, 'destroy'])->name('inventoryRequests.destroy');
        // Approve
        Route::post('/inventoryRequests/approve/{id}', [App\Http\Controllers\InventoryRequestController::class, 'approve'])->name('inventoryRequests.approve');
        // Reject
        Route::post('/inventoryRequests/reject/{id}', [App\Http\Controllers\InventoryRequestController::class, 'reject'])->name('inventoryRequests.reject');

        Route::get('/notification-counts', [App\Http\Controllers\InventoryRequestController::class, 'getNotificationCounts'])->name('notification.counts');


    });

    Route::group(['middleware' => ['permission:stockreturn']], function() {
    // Inventory Returns Routes
        Route::prefix('inventoryReturns')->name('inventoryReturns.')->group(function () {
            // List with DataTable
            Route::get('/', [App\Http\Controllers\InventoryReturnController::class, 'index'])->name('index');
            
            // AJAX endpoints for inventory data
            Route::get('/get-driver-inventory', [App\Http\Controllers\InventoryReturnController::class, 'getDriverInventory'])->name('getDriverInventory');
            Route::get('/get-product-batches', [App\Http\Controllers\InventoryReturnController::class, 'getProductBatches'])->name('getProductBatches');
            
            // CRUD operations
            Route::post('/store', [App\Http\Controllers\InventoryReturnController::class, 'store'])->name('store');
            Route::get('/show/{id}', [App\Http\Controllers\InventoryReturnController::class, 'show'])->name('show');
            Route::get('/with-batches/{id}', [App\Http\Controllers\InventoryReturnController::class, 'getReturnWithBatches'])->name('withBatches');
            Route::put('/update/{id}', [App\Http\Controllers\InventoryReturnController::class, 'update'])->name('update');
            Route::post('/update/{id}', [App\Http\Controllers\InventoryReturnController::class, 'update']); // Alternative for form submissions
            
            // Status management
            Route::post('/approve/{id}', [App\Http\Controllers\InventoryReturnController::class, 'approve'])->name('approve');
            Route::post('/reject/{id}', [App\Http\Controllers\InventoryReturnController::class, 'reject'])->name('reject');
            
            // Delete
            Route::delete('/delete/{id}', [App\Http\Controllers\InventoryReturnController::class, 'destroy'])->name('destroy');
            
            // Statistics
            Route::get('/statistics', [App\Http\Controllers\InventoryReturnController::class, 'statistics'])->name('statistics');
        });
    });
    
    Route::group(['middleware' => ['permission:stockcount']], function() {
        // Inventory Counts Routes
        Route::prefix('inventoryCounts')->name('inventoryCounts.')->group(function () {
            // List with DataTable
            Route::get('/', [App\Http\Controllers\InventoryCountController::class, 'index'])->name('index');
            
            // AJAX endpoints for inventory data
            Route::get('/get-driver-inventory', [App\Http\Controllers\InventoryCountController::class, 'getDriverInventory'])->name('getDriverInventory');
            Route::get('/get-product-batches', [App\Http\Controllers\InventoryCountController::class, 'getProductBatches'])->name('getProductBatches');
            Route::get('/get-product-inventory/{productId}', [App\Http\Controllers\InventoryCountController::class, 'getProductInventory'])->name('getProductInventory');
            
            // CRUD operations
            Route::post('/store', [App\Http\Controllers\InventoryCountController::class, 'store'])->name('store');
            Route::get('/show/{id}', [App\Http\Controllers\InventoryCountController::class, 'show'])->name('show');
            Route::get('/with-batches/{id}', [App\Http\Controllers\InventoryCountController::class, 'getCountWithBatches'])->name('withBatches');
            Route::put('/update/{id}', [App\Http\Controllers\InventoryCountController::class, 'update'])->name('update');
            Route::post('/update/{id}', [App\Http\Controllers\InventoryCountController::class, 'update']); // Alternative for form submissions
            
            // Status management
            Route::post('/approve/{id}', [App\Http\Controllers\InventoryCountController::class, 'approve'])->name('approve');
            Route::post('/reject/{id}', [App\Http\Controllers\InventoryCountController::class, 'reject'])->name('reject');
            
            // Delete
            Route::delete('/delete/{id}', [App\Http\Controllers\InventoryCountController::class, 'destroy'])->name('destroy');
            
            // Statistics
            Route::get('/statistics', [App\Http\Controllers\InventoryCountController::class, 'statistics'])->name('statistics');
        });
    });
    
    Route::group(['middleware' => ['permission:customer']], function() {
        Route::get('/customers/sync-xero', [App\Http\Controllers\CustomerController::class, 'syncXero']);
        Route::resource('customers', App\Http\Controllers\CustomerController::class);
        Route::post('/customers/massdestroy', [App\Http\Controllers\CustomerController::class, 'massdestroy']);
        Route::post('/customers/massupdatestatus', [App\Http\Controllers\CustomerController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:company']], function() {
        Route::resource('companies', App\Http\Controllers\CompanyController::class);
        Route::post('/companies/massdestroy', [App\Http\Controllers\CompanyController::class, 'massdestroy']);
    });
    Route::group(['middleware' => ['permission:specialprice']], function() {
        Route::resource('specialPrices', App\Http\Controllers\SpecialPriceController::class);
        Route::post('/specialPrices/massdestroy', [App\Http\Controllers\SpecialPriceController::class, 'massdestroy']);
        Route::post('/specialPrices/massupdatestatus', [App\Http\Controllers\SpecialPriceController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:foc']], function() {
        Route::resource('focs', App\Http\Controllers\focController::class);
        Route::post('/focs/massdestroy', [App\Http\Controllers\focController::class, 'massdestroy']);
        Route::post('/focs/massupdatestatus', [App\Http\Controllers\focController::class, 'massupdatestatus']);
    });
    Route::group(['middleware' => ['permission:assign']], function() {
        Route::get('/assigns/masscreate', [App\Http\Controllers\AssignController::class, 'masscreate'])->name('assigns.masscreate');
        Route::post('/assigns/massstore', [App\Http\Controllers\AssignController::class, 'massstore'])->name('assigns.massstore');
        Route::resource('assigns', App\Http\Controllers\AssignController::class);
        Route::post('/assigns/massdestroy', [App\Http\Controllers\AssignController::class, 'massdestroy']);
        Route::post('/customerfindgroup', [App\Http\Controllers\AssignController::class, 'customerfindgroup'])->name('assigns.customerfindgroup');
    });
    Route::group(['middleware' => ['permission:invoice']], function() {
        //Invoice
        Route::get('/invoices/sync-xero', [App\Http\Controllers\InvoiceController::class, 'syncXero']);
        Route::get('/invoices/{id}/detail', [App\Http\Controllers\InvoiceController::class, 'detail'])->name('invoices.detail');
        Route::post('/invoices/{id}/adddetail', [App\Http\Controllers\InvoiceController::class, 'adddetail'])->name('invoices.adddetail');
        Route::delete('/invoices/{id}/deletedetail', [App\Http\Controllers\InvoiceController::class, 'deletedetail'])->name('invoices.deletedetail');
        Route::patch('/invoices/{id}/updatedetail', [App\Http\Controllers\InvoiceController::class, 'updatedetail'])->name('invoices.updatedetail');
        Route::get('/invoices/customer/{id}', [App\Http\Controllers\InvoiceController::class, 'getcustomer']);
        Route::resource('invoices', App\Http\Controllers\InvoiceController::class);
        Route::post('/invoices/massdestroy', [App\Http\Controllers\InvoiceController::class, 'massdestroy']);
        Route::post('/invoices/massupdatestatus', [App\Http\Controllers\InvoiceController::class, 'massupdatestatus']);
        Route::post('/invoices/massupdateautocountinvoice', [App\Http\Controllers\InvoiceController::class, 'massupdateautocountinvoice']);

        //Invoice Detail
        Route::get('invoiceDetails/getprice/{invoice_id}/{product_id}', [App\Http\Controllers\InvoiceDetailController::class, 'getprices']);
        Route::resource('invoiceDetails', App\Http\Controllers\InvoiceDetailController::class);
        Route::post('/invoiceDetails/massdestroy', [App\Http\Controllers\InvoiceDetailController::class, 'massdestroy']);
        //Invoice Payment
        Route::get('/invoicePayments/customer-invoices/{id}', [App\Http\Controllers\InvoicePaymentController::class, 'getcustomerinvoice']);
        Route::post('invoicePayments/updatepayment/{id}', [App\Http\Controllers\InvoicePaymentController::class, 'updatepayment']);
        Route::get('invoicePayments/getpayment/{id}', [App\Http\Controllers\InvoicePaymentController::class, 'getpayment']);
        Route::get('invoicePayments/getinvoice', [App\Http\Controllers\InvoicePaymentController::class, 'getinvoice']);
        Route::post('invoicePayments/sync-autocount/{id}', [App\Http\Controllers\InvoicePaymentController::class, 'syncAutocount'])->name('invoicePayments.syncAutocount');
        Route::post('/invoicePayments/massSyncAutocount', [App\Http\Controllers\InvoicePaymentController::class, 'massSyncAutocount']);
        Route::resource('invoicePayments', App\Http\Controllers\InvoicePaymentController::class);
        Route::post('/invoicePayments/massupdatestatus', [App\Http\Controllers\InvoicePaymentController::class, 'massupdatestatus']);
        //Print Invoice
        Route::get('/print/invoices/getInvoiceViewPDF/{id}/{function}', [App\Http\Controllers\InvoiceController::class, 'getInvoiceViewPDF'])->name('invoice.print');

        Route::get('/print/invoices/getInvoiceSimplePDF/{id}/{function}', [App\Http\Controllers\InvoiceController::class, 'getInvoiceSimplePDF'])->name('invoice.print_pdf');
        Route::get('invoices/get-available-batches/{invoice_id}', [App\Http\Controllers\InvoiceController::class, 'getAvailableBatches'])->name('invoices.get-available-batches');
        Route::get('/inventoryBalances/{lorry_id}/products/{product_id}/batches', [App\Http\Controllers\InventoryBalanceController::class, 'getLorryProductBatches'])->name('inventoryBalances.get-lorry-product-batches');
        //Print Receipt
        Route::get('/print/invoicePayments/getReceiptViewPDF/{id}/{function}', [App\Http\Controllers\InvoicePaymentController::class, 'getReceiptViewPDF'])->name('invoicePayments.print');

        //E-Invoice
        Route::get('/einvoices', [App\Http\Controllers\EInvoiceController::class, 'index'])->name('einvoices.index');
        Route::post('/einvoices/submit', [App\Http\Controllers\EInvoiceController::class, 'submit'])->name('einvoices.submit');
        Route::post('/einvoices/submit-consolidated', [App\Http\Controllers\EInvoiceController::class, 'submitConsolidated'])->name('einvoices.submit-consolidated');
        Route::get('/einvoices/{id}', [App\Http\Controllers\EInvoiceController::class, 'show'])->name('einvoices.show');
        Route::get('/einvoices/{id}/view-document', [App\Http\Controllers\EInvoiceController::class, 'viewDocument'])->name('einvoices.view-document');
        Route::post('/einvoices/{id}/cancel', [App\Http\Controllers\EInvoiceController::class, 'cancelDocument'])->name('einvoices.cancel');
        Route::post('/einvoices/{id}/refresh-status', [App\Http\Controllers\EInvoiceController::class, 'refreshStatus'])->name('einvoices.refresh-status');
        Route::get('/einvoices/{id}/details', [App\Http\Controllers\EInvoiceController::class, 'getFullDetails'])->name('einvoices.details');
        Route::get('/consolidated-einvoices', [App\Http\Controllers\EInvoiceController::class, 'indexConsolidated'])->name('consolidated-einvoices.index');
        Route::get('/consolidated-einvoices/{id}', [App\Http\Controllers\EInvoiceController::class, 'showConsolidated'])->name('consolidated-einvoices.show');
        Route::get('/consolidated-einvoices/{id}/view-document', [App\Http\Controllers\EInvoiceController::class, 'viewConsolidatedDocument'])->name('consolidated-einvoices.view-document');
        Route::post('/consolidated-einvoices/{id}/cancel', [App\Http\Controllers\EInvoiceController::class, 'cancelConsolidatedDocument'])->name('consolidated-einvoices.cancel');
        Route::post('/consolidated-einvoices/{id}/refresh-status', [App\Http\Controllers\EInvoiceController::class, 'refreshConsolidatedStatus'])->name('consolidated-einvoices.refresh-status');
        Route::get('/consolidated-einvoices/{id}/details', [App\Http\Controllers\EInvoiceController::class, 'getConsolidatedFullDetails'])->name('consolidated-einvoices.details');

        //Credit Note
        Route::get('/credit-notes', [App\Http\Controllers\CreditNoteController::class, 'index'])->name('credit-notes.index');
        Route::get('/credit-notes/create', [App\Http\Controllers\CreditNoteController::class, 'create'])->name('credit-notes.create');
        Route::match(['get', 'post'], '/credit-notes/select-customer', [App\Http\Controllers\CreditNoteController::class, 'selectCustomer'])->name('credit-notes.select-customer');
        Route::post('/credit-notes/select-currency', [App\Http\Controllers\CreditNoteController::class, 'selectCurrency'])->name('credit-notes.select-currency');
        Route::post('/credit-notes/select-einvoice', [App\Http\Controllers\CreditNoteController::class, 'selectEinvoice'])->name('credit-notes.select-einvoice');
        Route::post('/credit-notes/update-einvoice', [App\Http\Controllers\CreditNoteController::class, 'updateEinvoice'])->name('credit-notes.update-einvoice');
        Route::post('/credit-notes/submit', [App\Http\Controllers\CreditNoteController::class, 'submitNote'])->name('credit-notes.submit');
        Route::post('/credit-notes', [App\Http\Controllers\CreditNoteController::class, 'store'])->name('credit-notes.store');
        Route::get('/credit-notes/{id}', [App\Http\Controllers\CreditNoteController::class, 'show'])->name('credit-notes.show');
        Route::post('/credit-notes/{id}/cancel', [App\Http\Controllers\CreditNoteController::class, 'cancel'])->name('credit-notes.cancel');

        // Debit Note Routes
        Route::get('/debit-notes', [App\Http\Controllers\DebitNoteController::class, 'index'])->name('debit-notes.index');
        Route::get('/debit-notes/create', [App\Http\Controllers\DebitNoteController::class, 'create'])->name('debit-notes.create');
        Route::match(['get', 'post'], '/debit-notes/select-customer', [App\Http\Controllers\DebitNoteController::class, 'selectCustomer'])->name('debit-notes.select-customer');
        Route::post('/debit-notes/select-currency', [App\Http\Controllers\DebitNoteController::class, 'selectCurrency'])->name('debit-notes.select-currency');
        Route::post('/debit-notes/select-einvoice', [App\Http\Controllers\DebitNoteController::class, 'selectEinvoice'])->name('debit-notes.select-einvoice');
        Route::post('/debit-notes/update-einvoice', [App\Http\Controllers\DebitNoteController::class, 'updateEinvoice'])->name('debit-notes.update-einvoice');
        Route::post('/debit-notes/submit', [App\Http\Controllers\DebitNoteController::class, 'submitNote'])->name('debit-notes.submit');
        Route::post('/debit-notes', [App\Http\Controllers\DebitNoteController::class, 'store'])->name('debit-notes.store');
        Route::get('/debit-notes/{id}', [App\Http\Controllers\DebitNoteController::class, 'show'])->name('debit-notes.show');
        Route::post('/debit-notes/{id}/cancel', [App\Http\Controllers\DebitNoteController::class, 'cancel'])->name('debit-notes.cancel');

    });
    Route::group(['middleware' => ['permission:task']], function() {
        Route::resource('tasks', App\Http\Controllers\TaskController::class);
        Route::resource('taskTransfers', App\Http\Controllers\TaskTransferController::class);
    });
    // Trips write actions: full-access users only.
    Route::group(['middleware' => ['permission:trip']], function() {
        Route::resource('trips', App\Http\Controllers\TripController::class)->except(['index', 'show']);
    });
    // Trips read-only: full-access users OR inventory managers (view only).
    Route::group(['middleware' => ['role_or_permission:trip|Inventory Admin']], function() {
        Route::get('trips', [App\Http\Controllers\TripController::class, 'index'])->name('trips.index');
        Route::get('trips/view-report/{trip_uuid}/{driver_id}', [App\Http\Controllers\TripController::class, 'viewTripReport'])->name('trips.viewReport');
        Route::get('trips/{trip}', [App\Http\Controllers\TripController::class, 'show'])->name('trips.show');
    });
    // Warehouse Routes
    Route::group(['middleware' => ['permission:warehouse']], function() {
        
        // Main CRUD routes
        Route::get('/warehouses', [App\Http\Controllers\WarehouseController::class, 'index'])->name('warehouses.index');
        Route::get('/warehouses/create', [App\Http\Controllers\WarehouseController::class, 'create'])->name('warehouses.create');
        Route::post('/warehouses', [App\Http\Controllers\WarehouseController::class, 'store'])->name('warehouses.store');
        Route::get('/warehouses/{id}', [App\Http\Controllers\WarehouseController::class, 'show'])->name('warehouses.show');
        Route::get('/warehouses/{id}/edit', [App\Http\Controllers\WarehouseController::class, 'edit'])->name('warehouses.edit');
        Route::match(['put', 'patch'], '/warehouses/{id}', [App\Http\Controllers\WarehouseController::class, 'update'])->name('warehouses.update');
        Route::get('warehouses/{warehouseId}/all-batches', [App\Http\Controllers\WarehouseController::class, 'getAllBatches'])->name('warehouses.get-all-batches');

        Route::post('warehouses/stock-adjustment', [App\Http\Controllers\WarehouseController::class, 'stockAdjustment'])->name('warehouses.stock-adjustment');
        Route::post('warehouses/stock-out', [App\Http\Controllers\WarehouseController::class, 'stockOut'])->name('warehouses.stock-out');
        Route::get('warehouses/get-warehouse-products/{warehouseId}', [App\Http\Controllers\WarehouseController::class, 'getWarehouseProducts'])->name('warehouses.get-warehouse-products');
        
        // AJAX routes for getting batches/inventory
        Route::get('/warehouses/get-warehouse-batches/{warehouse_id}', [App\Http\Controllers\WarehouseController::class, 'getWarehouseBatches'])->name('warehouses.get-warehouse-batches');
        Route::get('/warehouses/get-product-batches/{product_id}', [App\Http\Controllers\WarehouseController::class, 'getProductBatches'])->name('warehouses.get-product-batches');
        Route::get('/warehouses/{warehouse_id}/inventory', [App\Http\Controllers\WarehouseController::class, 'getWarehouseInventory'])->name('warehouses.get-inventory');
        Route::get('/warehouses/{warehouse_id}/summary', [App\Http\Controllers\WarehouseController::class, 'getWarehouseSummary'])->name('warehouses.summary');
        
        // Specific inventory item management
        Route::get('/warehouses/{warehouse_id}/inventory/{inventory_id}/edit', [App\Http\Controllers\WarehouseController::class, 'editInventory'])->name('warehouses.edit-inventory');
        Route::put('/warehouses/{warehouse_id}/inventory/{inventory_id}', [App\Http\Controllers\WarehouseController::class, 'updateInventory'])->name('warehouses.update-inventory');
        Route::delete('/warehouses/{warehouse_id}/inventory/{inventory_id}', [App\Http\Controllers\WarehouseController::class, 'destroyInventory'])->name('warehouses.destroy-inventory');
        
        // Transfer between warehouses
        Route::post('/warehouses/transfer', [App\Http\Controllers\WarehouseController::class, 'transferStock'])->name('warehouses.transfer');
        Route::get('/warehouses/transfer-form', [App\Http\Controllers\WarehouseController::class, 'showTransferForm'])->name('warehouses.transfer-form');
    
        // Get products and batches for dropdowns (AJAX)
        Route::get('/warehouses/get-products', [App\Http\Controllers\WarehouseController::class, 'getProducts'])->name('warehouses.get-products');
        Route::get('/warehouses/get-batches', [App\Http\Controllers\WarehouseController::class, 'getBatches'])->name('warehouses.get-batches');
        Route::get('/warehouses/get-warehouses-list', [App\Http\Controllers\WarehouseController::class, 'getWarehousesList'])->name('warehouses.get-list');
        Route::get('/warehouses/{warehouse_id}/products/{product_id}/batches', [App\Http\Controllers\WarehouseController::class, 'getWarehouseProductBatches'])->name('warehouses.get-warehouse-product-batches');

        // Batch stock-in approval requests (view: anyone with warehouse access; actions gated by role in controller)
        Route::get('/stockInRequests', [App\Http\Controllers\StockInRequestController::class, 'index'])->name('stockInRequests.index');
        Route::post('/stockInRequests/bulk-approve', [App\Http\Controllers\StockInRequestController::class, 'bulkApprove'])->name('stockInRequests.bulk-approve');
        Route::post('/stockInRequests/bulk-reject', [App\Http\Controllers\StockInRequestController::class, 'bulkReject'])->name('stockInRequests.bulk-reject');
        Route::post('/stockInRequests/{id}/approve', [App\Http\Controllers\StockInRequestController::class, 'approve'])->name('stockInRequests.approve');
        Route::post('/stockInRequests/{id}/reject', [App\Http\Controllers\StockInRequestController::class, 'reject'])->name('stockInRequests.reject');
        Route::post('/stockInRequests/{id}/update-qty', [App\Http\Controllers\StockInRequestController::class, 'updateQty'])->name('stockInRequests.update-qty');
    });

    Route::group(['middleware' => ['permission:inventorybalance']], function() {
        Route::get('/inventoryBalances', [App\Http\Controllers\InventoryBalanceController::class, 'index'])->name('inventoryBalances.index');
        Route::post('/inventoryBalances/stockin', [App\Http\Controllers\InventoryBalanceController::class, 'stockin'])->name('inventoryBalances.stockin');
        Route::get('/inventoryBalances/getstock/{lorry_id}/{product_id}', [App\Http\Controllers\InventoryBalanceController::class, 'getstock'])->name('inventoryBalances.getstock');
        Route::post('/inventoryBalances/stockout', [App\Http\Controllers\InventoryBalanceController::class, 'stockout'])->name('inventoryBalances.stockout');
    
        Route::get('/inventoryBalances/get-lorry-batches/{lorry_id}', [App\Http\Controllers\InventoryBalanceController::class, 'getLorryBatches'])->name('inventoryBalances.get-lorry-batches');

        // For stock out - get warehouses that have a specific batch (USE InventoryBalanceController)
        Route::get('/warehouses/with-batch/{batch_id}', [App\Http\Controllers\InventoryBalanceController::class, 'getWarehousesWithBatch'])->name('warehouses.get-warehouses-with-batch');
        
    });
    Route::group(['middleware' => ['permission:inventorytransaction']], function() {
        Route::get('/inventoryTransactions/index', [App\Http\Controllers\InventoryTransactionController::class, 'index'])->name('inventoryTransactions.index');
        Route::get('/warehouseTransactions/index', [App\Http\Controllers\InventoryTransactionController::class, 'warehouseIndex'])->name('warehouseTransactions.index');
        Route::get('/inventoryTransactions/show/{id}', [App\Http\Controllers\InventoryTransactionController::class, 'show'])->name('inventoryTransactions.show');

    });
    Route::group(['middleware' => ['permission:inventorytransfer']], function() {
        Route::get('/inventoryTransfers', [App\Http\Controllers\InventoryTransferController::class, 'index'])->name('inventoryTransfers.index');
    });

    Route::get('/notification-counts', [App\Http\Controllers\InventoryRequestController::class, 'getNotificationCounts'])->name('notification.counts');

    Route::group(['middleware' => ['permission:code']], function() {
        Route::resource('codes', App\Http\Controllers\CodeController::class);
    });

    Route::group(['middleware' => ['permission:code']], function() {
        Route::prefix('language')->group(function() {
            Route::get('/', [App\Http\Controllers\LanguageController::class, 'index'])->name('language.index');
            Route::post('/change', [App\Http\Controllers\LanguageController::class, 'changeLanguage'])->name('language.change');
            Route::post('/save', [App\Http\Controllers\LanguageController::class, 'saveTranslations'])->name('language.save');
            Route::post('/import', [App\Http\Controllers\LanguageController::class, 'importLanguage'])->name('language.import');
            Route::delete('/language/{id}', [App\Http\Controllers\LanguageController::class, 'deleteLanguage'])->name('language.delete');
            Route::post('/language/export', [App\Http\Controllers\LanguageController::class, 'exportTranslations'])->name('language.export');
            Route::post('/language/import/file', [App\Http\Controllers\LanguageController::class, 'importTranslations'])->name('language.import.file');
        });
    });
    
    Route::group(['middleware' => ['permission:code']], function() {
        Route::prefix('mobile_language')->group(function() {
            Route::get('/', [App\Http\Controllers\MobileLanguageController::class, 'index'])->name('mobile_language.index');
            Route::get('/edit/{id}', [App\Http\Controllers\MobileLanguageController::class, 'edit'])->name('mobile_language.edit');
            Route::post('/save', [App\Http\Controllers\MobileLanguageController::class, 'saveTranslations'])->name('mobile_language.save');
            Route::delete('/delete/{id}', [App\Http\Controllers\MobileLanguageController::class, 'deleteLanguage'])->name('mobile_language.destroy');
            Route::post('/import', [App\Http\Controllers\MobileLanguageController::class, 'importLanguage'])->name('mobile_language.import');

            Route::post('/export', [App\Http\Controllers\MobileLanguageController::class, 'exportTranslations'])->name('mobile_language.export');
            Route::post('/import-file', [App\Http\Controllers\MobileLanguageController::class, 'importTranslations'])->name('mobile_language.import.file');
        });
    });

    Route::resource('activitylogs', App\Http\Controllers\ActivityLogController::class);


    Route::get('/language/load', [LanguageController::class, 'loadTranslations'])->name('language.load');   
    
    Route::group(['middleware' => ['permission:code']], function() {
        Route::resource('customer_group', App\Http\Controllers\CustomerGroupController::class);
    });
    Route::group(['middleware' => ['permission:code']], function() {
        Route::resource('commission_group', App\Http\Controllers\CommissionGroupController::class);
    });
    Route::group(['middleware' => ['permission:user']], function() {
        Route::resource('users', App\Http\Controllers\UserController::class);
        Route::resource('Managerusers', App\Http\Controllers\ManagerUserController::class);

    });
    Route::group(['middleware' => ['permission:userrole']], function() {
        Route::resource('userHasRoles', App\Http\Controllers\UserHasRoleController::class);
    });
    Route::group(['middleware' => ['permission:role']], function() {
        Route::resource('roles', App\Http\Controllers\RoleController::class);
    });
    Route::group(['middleware' => ['permission:rolepermission']], function() {
        Route::resource('roleHasPermissions', App\Http\Controllers\RoleHasPermissionController::class);
    });
    if( env('APP_ENV') == 'local'){
        Route::resource('permissions', App\Http\Controllers\PermissionController::class);
        // Route::resource('reportdetails', App\Http\Controllers\ReportdetailController::class);
    }
});
