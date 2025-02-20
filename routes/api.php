<?php

use App\Http\Controllers\api\AccountCreationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// account registration routes
Route::post('register', [AccountCreationController::class, 'register']);
Route::post('verify/otp/{id}', [AccountCreationController::class, 'verifyOtp']);
Route::post('account/register', [AccountCreationController::class, 'accountRegister']);
Route::post('user/login', [AccountCreationController::class, 'Userlogin'])->name('login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('account/info/{id}', [AccountCreationController::class, 'accountInfo']);
    
});
