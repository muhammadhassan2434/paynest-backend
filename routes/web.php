<?php

use App\Http\Controllers\admin\AdvertisementController;
use App\Http\Controllers\admin\Authcontroller;
use App\Http\Controllers\admin\dashboardController;
use App\Http\Controllers\admin\ServiceController;
use App\Http\Controllers\admin\ServiceProviderController;
use App\Http\Middleware\AdminAuthMiddleware;
use Illuminate\Support\Facades\Artisan;
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

    // advertisement routes
    Route::get('advertisements/index',[AdvertisementController::class, 'index'])->name('advertisements.index');
    Route::get('advertisements/create',[AdvertisementController::class, 'create'])->name('advertisements.create');
    Route::post('advertisements/store',[AdvertisementController::class, 'store'])->name('advertisements.store');
    Route::get('advertisements/edit/{id}',[AdvertisementController::class, 'edit'])->name('advertisements.edit');
    Route::put('advertisements/update/{id}',[AdvertisementController::class, 'update'])->name('advertisements.update');
    Route::delete('advertisements/delete/{id}',[AdvertisementController::class, 'destroy'])->name('advertisements.destroy');
});
Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('optimize:clear');
    Artisan::call('view:clear');
    return "Cache Cleared!";
});
