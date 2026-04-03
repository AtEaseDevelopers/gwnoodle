<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1'], function () {
    Route::get('/testconnection', function (Request $request) {
        return 'OK';
    });
    //language
    Route::get('/supported-languages', [App\Http\Controllers\Api\V1\DriverController::class, 'getAllLanguages']);
    Route::post('/language', [App\Http\Controllers\Api\V1\DriverController::class, 'getTranslations']);

    //Auth
    Route::post('/driver/login', [App\Http\Controllers\Api\V1\DriverController::class, 'login']);
    Route::post('/driver/location', [App\Http\Controllers\Api\V1\DriverController::class, 'location']);
    Route::post('/driver/logout', [App\Http\Controllers\Api\V1\DriverController::class, 'logout']);
    Route::post('/driver/session', [App\Http\Controllers\Api\V1\DriverController::class, 'session']);
    //Trip
    Route::get('/driver/trip', [App\Http\Controllers\Api\V1\DriverController::class, 'checktrip']);
    Route::post('/driver/trip/start', [App\Http\Controllers\Api\V1\DriverController::class, 'starttrip']);
    Route::post('/driver/trip/end', [App\Http\Controllers\Api\V1\DriverController::class, 'tripEnd']);

    //Kelindan
    // Route::get('/driver/kelindan', [App\Http\Controllers\Api\V1\DriverController::class, 'getkelindan']);
    //Lorry
    Route::get('/driver/lorry', [App\Http\Controllers\Api\V1\DriverController::class, 'getlorry']);
    //Task
    Route::get('/driver/task', [App\Http\Controllers\Api\V1\DriverController::class, 'gettask']);
    Route::get('/driver/taskpage', [App\Http\Controllers\Api\V1\DriverController::class, 'gettaskpage']);
    Route::post('/driver/task/start', [App\Http\Controllers\Api\V1\DriverController::class, 'starttask']);
    Route::post('/driver/task/cancel', [App\Http\Controllers\Api\V1\DriverController::class, 'canceltask']);

    //Product
    Route::post('/driver/product', [App\Http\Controllers\Api\V1\DriverController::class, 'getproduct']);

    //Customer
    Route::get('/driver/customer', [App\Http\Controllers\Api\V1\DriverController::class, 'getcustomer']);
    Route::post('/driver/customer/detail', [App\Http\Controllers\Api\V1\DriverController::class, 'customerdetail']);
    Route::post('/driver/customer/makepayment', [App\Http\Controllers\Api\V1\DriverController::class, 'customermakepayment']);
    Route::post('/driver/customer/invoice', [App\Http\Controllers\Api\V1\DriverController::class, 'customerinvoice']);
    Route::post('/driver/customer/payment', [App\Http\Controllers\Api\V1\DriverController::class, 'customerpayment']);

    //Invoice
    Route::get('/driver/get-invoice-list/{customer_id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'getInvoice']);
    Route::get('/driver/get-invoice-detail/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'getInvoiceById']);
    Route::get('/driver/get-invoice-no', [App\Http\Controllers\Api\V1\DriverController::class, 'getInvoiceNo']);

    Route::post('/driver/invoice', [App\Http\Controllers\Api\V1\DriverController::class, 'addinvoice']);
    Route::post('/driver/invoice/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'editInvoice']);
    Route::post('/driver/invoice/pdf', [App\Http\Controllers\Api\V1\DriverController::class, 'invoicepdf']);

     //Invoice Payment
    Route::post('/driver/invoicepayment', [App\Http\Controllers\Api\V1\DriverController::class, 'addpayment']);
    Route::get('/driver/get-customer-invoice-list/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'getcustomerinvoice']);
    Route::post('/driver/invoicepayment/pdf', [App\Http\Controllers\Api\V1\DriverController::class, 'paymentpdf']);

    //Stock
    Route::get('/driver/stock', [App\Http\Controllers\Api\V1\DriverController::class, 'getstock']);
    Route::get('/driver/stock/listdriver', [App\Http\Controllers\Api\V1\DriverController::class, 'listotherdriver']);
    Route::post('/driver/stock/transfer', [App\Http\Controllers\Api\V1\DriverController::class, 'transferstock']);
    Route::post('/driver/stock/transaction', [App\Http\Controllers\Api\V1\DriverController::class, 'getstocktransaction']);
    Route::get('/driver/stock/transfer', [App\Http\Controllers\Api\V1\DriverController::class, 'gettransfer']);
    Route::get('/driver/stock/transfer/update', [App\Http\Controllers\Api\V1\DriverController::class, 'updatetransfer']);
    //Task transfer
    // Route::get('/driver/task/listdriver', [App\Http\Controllers\Api\V1\DriverController::class, 'listalldriver']);
    // Route::post('/driver/task/driver', [App\Http\Controllers\Api\V1\DriverController::class, 'getdrivertask']);
    // Route::post('/driver/task/pull', [App\Http\Controllers\Api\V1\DriverController::class, 'pulldrivertask']);
    // Route::post('/driver/task/push', [App\Http\Controllers\Api\V1\DriverController::class, 'pushdrivertask']);
    // Route::get('/driver/task/listtranfer', [App\Http\Controllers\Api\V1\DriverController::class, 'listtranfer']);
    //dashboard
    Route::get('/driver/dashboard', [App\Http\Controllers\Api\V1\DriverController::class, 'dashboard']);

    Route::get('/driver/inventory-balance', [App\Http\Controllers\Api\V1\DriverController::class, 'getInventoryBalance']);
    Route::get('/driver/stock/transaction', [App\Http\Controllers\Api\V1\DriverController::class, 'getInventoryTransaction']);

    Route::get('/driver/get-product', [App\Http\Controllers\Api\V1\DriverController::class, 'getAllProduct']);
    Route::post('/driver/stock-request', [App\Http\Controllers\Api\V1\DriverController::class, 'StockRequest']);
    Route::post('/driver/stock-count', [App\Http\Controllers\Api\V1\DriverController::class, 'StockCount']);
    Route::get('/driver/stock-count-status', [App\Http\Controllers\Api\V1\DriverController::class, 'StockCountStatus']);
    Route::get('/driver/driver-stock-request-list', [App\Http\Controllers\Api\V1\DriverController::class, 'getStockRequestRecord']);
    Route::get('/driver/driver-stock-return-list', [App\Http\Controllers\Api\V1\DriverController::class, 'getStockReturnRecord']);






    //manager
    Route::get('/driver/get-stock-out-warehouse', [App\Http\Controllers\Api\V1\DriverController::class, 'getStockOutWarehouseInventory']);
    Route::get('/driver/get-warehouse-balance/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'getWarehouseInventory']);
    Route::get('/driver/get-driver-product/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'getDriverProduct']);

    Route::get('/driver/stock-count-list/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'getStockCount']);

    Route::post('/driver/stock-count-approve', [App\Http\Controllers\Api\V1\DriverController::class, 'approveStockCount']);
    Route::post('/driver/stock-return', [App\Http\Controllers\Api\V1\DriverController::class, 'StockReturn']);

    Route::post('/driver/manager-login', [App\Http\Controllers\Api\V1\DriverController::class, 'managerLogin']);
    Route::post('/driver/manager-logout', [App\Http\Controllers\Api\V1\DriverController::class, 'managerLogout']);

    //stock in 
    Route::post('/driver/stock-in', [App\Http\Controllers\Api\V1\DriverController::class, 'Stockin']);
    Route::get('/driver/get-warehouse-batches/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetWarehouseBatches']);

    // stock out
    Route::post('/driver/stock-out', [App\Http\Controllers\Api\V1\DriverController::class, 'Stockout']);
    Route::get('/driver/get-lorry-batches/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetLorryBatches']);
    Route::get('/driver/get-available-lorry', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetAvailableLorry']);

    //warehouse
    Route::post('/driver/create-warehouse', [App\Http\Controllers\Api\V1\DriverController::class, 'apiCreateWarehouse']);
    Route::post('/driver/update-warehouse/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiUpdateWarehouse']);
    //transfer stock
    Route::post('/driver/stock-transfer', [App\Http\Controllers\Api\V1\DriverController::class, 'apiTransferStock']);

    //product
    Route::post('/driver/create-product', [App\Http\Controllers\Api\V1\DriverController::class, 'apiCreateProduct']);
    Route::post('/driver/update-product/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiUpdateProduct']);
    Route::get('/driver/get-product-listing/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetProducts']);
    
    Route::get('/driver/get-product-unit-code', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetUnitCodeOptionsWithCount']);
    Route::get('/driver/get-classification-code', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetClassificationCodes']);

    //productbatch
    Route::post('/driver/create-product-batch', [App\Http\Controllers\Api\V1\DriverController::class, 'apiCreateProductBatch']);
    Route::post('/driver/update-product-batch/{id}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiUpdateProductBatch']);
    Route::get('/driver/get-product-batch-listing/{id?}', [App\Http\Controllers\Api\V1\DriverController::class, 'apiGetProductBatches']);

    Route::post('/driver/stock-in-product-batch', [App\Http\Controllers\Api\V1\DriverController::class, 'apiStockIn']);
    Route::post('/driver/stock-out-product-batch', [App\Http\Controllers\Api\V1\DriverController::class, 'apiStockOut']);


});

Route::namespace('API')->group(function () {
   
    // product
    Route::post('/products/update', 'ProductController@update');

    // customer
    Route::post('/customers/update', 'CustomerController@update');
       
    // invoices
    Route::get('/invoice/pending', 'InvoiceController@syncPending');
    Route::post('/invoice/update', 'InvoiceController@update');
    Route::post('/invoice/update-log', 'InvoiceController@updateLog');


});

Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
});
