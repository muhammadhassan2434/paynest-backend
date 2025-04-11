<?php

use App\Http\Controllers\api\AccountCreationController;
use App\Http\Controllers\api\BillReminderController;
use App\Http\Controllers\api\PaynestTransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// account registration routes
Route::post('register', [AccountCreationController::class, 'register']);
Route::post('verify/otp/{id}', [AccountCreationController::class, 'verifyOtp']);
Route::post('account/register', [AccountCreationController::class, 'accountRegister']);
Route::post('verify/phone/otp/{id}', [AccountCreationController::class, 'verifyPhoneOtp']);
Route::post('user/login', [AccountCreationController::class, 'Userlogin'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('account/info/{id}', [AccountCreationController::class, 'accountInfo']);
    
});



// paynest transfer routes
Route::post('validate/paynest/number',[PaynestTransferController::class,'ValidatePaynestNumber']);
Route::post('validate/enteramount',[PaynestTransferController::class,'enterAmount']);
Route::post('paynest/transfer',[PaynestTransferController::class,'PaynestTransfer']);

// bill reminders routes
Route::get('billreminders',[BillReminderController::class,'index']);
Route::post('store/billreminder',[BillReminderController::class,'store']);