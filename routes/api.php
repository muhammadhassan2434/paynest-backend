<?php

use App\Http\Controllers\api\AccountCreationController;
use App\Http\Controllers\api\BillPaymentController;
use App\Http\Controllers\api\BillReminderController;
use App\Http\Controllers\api\FetchServiceController;
use App\Http\Controllers\api\PaynestTransferController;
use App\Http\Controllers\api\ShedulePaymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// account registration routes
Route::post('register', [AccountCreationController::class, 'register']);
Route::post('verify/otp', [AccountCreationController::class, 'verifyOtp']);
Route::post('account/register', [AccountCreationController::class, 'accountRegister']);
Route::post('verify/phone/otp/{id}', [AccountCreationController::class, 'verifyPhoneOtp']);
Route::post('user/login', [AccountCreationController::class, 'Userlogin'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('account/info/{id}', [AccountCreationController::class, 'accountInfo']);
    
    // fetch services and service providers
    Route::get('services',[FetchServiceController::class,'index']);
    Route::get('service/providers',[FetchServiceController::class,'serviceProvider']);
    
    
    // paynest transfer routes
    Route::post('validate/paynest/number',[PaynestTransferController::class,'ValidatePaynestNumber']);
    Route::post('validate/enteramount',[PaynestTransferController::class,'enterAmount']);
    Route::post('paynest/transfer',[PaynestTransferController::class,'PaynestTransfer']);
    
    // bill reminders routes
    Route::get('billreminders/{id}',[BillReminderController::class,'index']);
    Route::get('pending/billreminders/{id}',[BillReminderController::class,'pendingbillreminders']);
    Route::get('success/billreminders/{id}',[BillReminderController::class,'successbillreminders']);
    Route::post('store/billreminder',[BillReminderController::class,'store']);
    Route::get('edit/billreminder/{id}',[BillReminderController::class,'edit']);
    Route::put('update/billreminder/{id}',[BillReminderController::class,'update']);
    Route::get('delete/billreminder/{id}',[BillReminderController::class,'delete']);
    
    Route::prefix('payment-schedules')->group(function () {
        Route::post('/all', [ShedulePaymentController::class, 'index']); 
        Route::post('/executed', [ShedulePaymentController::class, 'executed']); 
        Route::post('/cancelled', [ShedulePaymentController::class, 'cancelled']); 
        Route::post('/failed', [ShedulePaymentController::class, 'failed']); 
        Route::post('/refunded', [ShedulePaymentController::class, 'redunded']); 
        Route::post('/', [ShedulePaymentController::class, 'store']); 
        Route::post('/refund/{id}', [ShedulePaymentController::class, 'refundOnly']); 
        Route::post('/cancel/{id}', [ShedulePaymentController::class, 'cancel']);
        Route::post('/refund-back/{id}', [ShedulePaymentController::class, 'refundBack']);
    });
    
    // bil payment routes
    Route::post('billpayments',[BillPaymentController::class,'index']);
    Route::post('billpayments/failed',[BillPaymentController::class,'failed']);
    Route::post('validate/consumer/number',[BillPaymentController::class,'validateConsumernumber']);
    Route::post('billpayment/store',[BillPaymentController::class,'store']);
    Route::get('service/provider/electricity/bill',[BillPaymentController::class,'serviceProviderElectricityBill']);
    Route::get('service/provider/gas/bill',[BillPaymentController::class,'serviceProviderGasBill']);
    
    
    
});

