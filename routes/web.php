<?php
use App\Http\Controllers\KioskController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/',[KioskController::class,'index'])->name('kiosk.home');
Route::get('/products/{id}',[KioskController::class,'product'])->name('kiosk.product');
Route::post('/cart/add',[KioskController::class,'addToCart'])->name('cart.add');
Route::get('/cart',[KioskController::class,'cart'])->name('cart.index');
Route::post('/cart/update',[KioskController::class,'updateCart'])->name('cart.update');
Route::post('/cart/remove',[KioskController::class,'removeCart'])->name('cart.remove');
Route::get('/checkout',[KioskController::class,'checkout'])->name('checkout');
Route::post('/checkout',[KioskController::class,'placeOrder'])->name('checkout.place');
Route::get('/order/{orderNumber}',[KioskController::class,'confirmation'])->name('order.confirmation');

Route::get('/admin/login',[AdminAuthController::class,'showLogin'])->name('admin.login');
Route::post('/admin/login',[AdminAuthController::class,'login'])->name('admin.login.submit');
Route::post('/admin/logout',[AdminAuthController::class,'logout'])->name('admin.logout');

Route::middleware('admin')->prefix('admin')->name('admin.')->group(function(){
 Route::get('/',fn()=>redirect()->route('admin.dashboard'))->name('home');
 Route::get('/dashboard',[DashboardController::class,'index'])->name('dashboard');
 Route::resource('/products',ProductController::class)->except(['show']);
 Route::resource('/categories',CategoryController::class)->except(['show']);
 Route::get('/orders',[OrderController::class,'index'])->name('orders.index');
 Route::get('/orders/{id}',[OrderController::class,'show'])->name('orders.show');
 Route::patch('/orders/{id}/status',[OrderController::class,'status'])->name('orders.status');
});