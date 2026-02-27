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
    Route::post('/driver/trip/end', [App\Http\Controllers\Api\V1\DriverController::class, 'endtrip']);
    Route::post('/driver/trip', [App\Http\Controllers\Api\V1\DriverController::class, 'trip']);
    //Kelindan
    Route::get('/driver/kelindan', [App\Http\Controllers\Api\V1\DriverController::class, 'getkelindan']);
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
    Route::post('/driver/invoice', [App\Http\Controllers\Api\V1\DriverController::class, 'addinvoice']);
    Route::post('/driver/invoice/pdf', [App\Http\Controllers\Api\V1\DriverController::class, 'invoicepdf']);

     //Invoice Payment
    Route::post('/driver/invoicepayment', [App\Http\Controllers\Api\V1\DriverController::class, 'addpayment']);
    Route::post('/driver/invoicepayment/pdf', [App\Http\Controllers\Api\V1\DriverController::class, 'paymentpdf']);

    //Stock
    Route::get('/driver/stock', [App\Http\Controllers\Api\V1\DriverController::class, 'getstock']);
    Route::get('/driver/stock/listdriver', [App\Http\Controllers\Api\V1\DriverController::class, 'listotherdriver']);
    Route::post('/driver/stock/transfer', [App\Http\Controllers\Api\V1\DriverController::class, 'transferstock']);
    Route::post('/driver/stock/transaction', [App\Http\Controllers\Api\V1\DriverController::class, 'getstocktransaction']);
    Route::get('/driver/stock/transfer', [App\Http\Controllers\Api\V1\DriverController::class, 'gettransfer']);
    Route::get('/driver/stock/transfer/update', [App\Http\Controllers\Api\V1\DriverController::class, 'updatetransfer']);
    //Task transfer
    Route::get('/driver/task/listdriver', [App\Http\Controllers\Api\V1\DriverController::class, 'listalldriver']);
    Route::post('/driver/task/driver', [App\Http\Controllers\Api\V1\DriverController::class, 'getdrivertask']);
    Route::post('/driver/task/pull', [App\Http\Controllers\Api\V1\DriverController::class, 'pulldrivertask']);
    Route::post('/driver/task/push', [App\Http\Controllers\Api\V1\DriverController::class, 'pushdrivertask']);
    Route::get('/driver/task/listtranfer', [App\Http\Controllers\Api\V1\DriverController::class, 'listtranfer']);
    //dashboard
    Route::post('/driver/dashboard', [App\Http\Controllers\Api\V1\DriverController::class, 'dashboard']);


});


Route::fallback(function(){
    return response()->json([
        'message' => 'Page Not Found. If error persists, contact info@website.com'], 404);
});
