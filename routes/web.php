<?php

use App\Http\Controllers\admin\Authcontroller;
use App\Http\Controllers\admin\dashboardController;
use App\Http\Controllers\admin\ServiceController;
use App\Http\Controllers\admin\ServiceProviderController;
use App\Http\Middleware\AdminAuthMiddleware;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('admin/login', [Authcontroller::class, 'login'])->name('admin.login');
Route::post('admin/auth', [Authcontroller::class, 'auth'])->name('admin.auth');

Route::middleware(AdminAuthMiddleware::class)->group(function () {
    // dashboard routes
    Route::get('/', [dashboardController::class, 'index'])->name('dashboard.index');

    // admin logout route
    Route::get('admin/logout', [Authcontroller::class, 'logout'])->name('admin.logout');

    // services routes
    Route::resource('services', ServiceController::class);

    // service providers routes
    Route::resource('service/provider', ServiceProviderController::class);
});
