<?php

use App\Http\Controllers\KioskController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;

Route::middleware('kiosk')->group(function(){
    Route::get('/', [KioskController::class,'index'])->name('kiosk.home');
    Route::get('/menu', [KioskController::class,'catalog'])->name('kiosk.catalog');
    Route::get('/products/{sku}', [KioskController::class,'product'])->name('kiosk.product');
    Route::post('/cart/add', [KioskController::class,'addToCart'])->name('cart.add');
    Route::get('/cart', [KioskController::class,'cart'])->name('cart.index');
    Route::post('/cart/update', [KioskController::class,'updateCart'])->name('cart.update');
    Route::post('/cart/remove', [KioskController::class,'removeCart'])->name('cart.remove');
    Route::post('/checkout', [KioskController::class,'placeOrder'])->name('checkout.place');
    Route::get('/order/{orderNumber}', [KioskController::class,'confirmation'])->name('order.confirmation');
});

Route::get('/admin', [AdminAuthController::class,'showLogin'])->name('admin.entry');
Route::get('/admin/login', [AdminAuthController::class,'showLogin'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class,'login'])->name('admin.login.submit');
Route::post('/admin/logout', [AdminAuthController::class,'logout'])->name('admin.logout');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function(){
    // Both administrator and cashier accounts.
    Route::get('/dashboard', [DashboardController::class,'index'])->name('dashboard');
    Route::get('/pos', [OrderController::class,'pos'])->name('pos');
    Route::get('/orders', [OrderController::class,'index'])->name('orders.index');
    Route::get('/orders/{id}', [OrderController::class,'show'])->name('orders.show');
    Route::patch('/orders/{id}/status', [OrderController::class,'status'])->name('orders.status');

    // Administrator-only management.
    Route::middleware('admin_only')->group(function(){
        Route::get('/inventory', [InventoryController::class,'index'])->name('inventory.index');
        Route::get('/activity', [ActivityController::class,'index'])->name('activity.index');

        Route::get('/reports', [ReportController::class,'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class,'export'])->name('reports.export');

        Route::get('/settings', [SettingsController::class,'index'])->name('settings.index');
        Route::put('/settings', [SettingsController::class,'update'])->name('settings.update');
        Route::post('/settings/reset', [SettingsController::class,'reset'])->name('settings.reset');

        Route::resource('/products', ProductController::class)->except(['show']);
        Route::resource('/categories', CategoryController::class)->except(['show']);

        Route::get('/staff', [StaffController::class,'index'])->name('staff.index');
        Route::get('/staff/create', [StaffController::class,'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class,'store'])->name('staff.store');
        Route::get('/staff/{id}/edit', [StaffController::class,'edit'])->name('staff.edit');
        Route::put('/staff/{id}', [StaffController::class,'update'])->name('staff.update');
        Route::patch('/staff/{id}/toggle', [StaffController::class,'toggle'])->name('staff.toggle');
        Route::delete('/staff/{id}', [StaffController::class,'destroy'])->name('staff.destroy');
    });
});
