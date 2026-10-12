<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ข้อมูลสินค้า (JSON)
Route::get('/api/products', [\App\Http\Controllers\ProductController::class, 'index']);
Route::get('/api/products/{id}', [\App\Http\Controllers\ProductController::class, 'show']);

Route::middleware('guest')->group(function () {
    Route::get('/register', [\App\Http\Controllers\AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register']);
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [\App\Http\Controllers\ProfileController::class, 'show'])->name('profile');
});

Route::middleware('auth')->group(function () {
    Route::get('/cart', [\App\Http\Controllers\CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [\App\Http\Controllers\CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/update/{variant_id}', [\App\Http\Controllers\CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/remove/{variant_id}', [\App\Http\Controllers\CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [\App\Http\Controllers\CartController::class, 'clear'])->name('cart.clear');
});

Route::middleware('auth')->group(function () {
    Route::get('/checkout', [\App\Http\Controllers\OrderController::class, 'showCheckout'])->name('orders.checkout_show');
    Route::post('/checkout', [\App\Http\Controllers\OrderController::class, 'checkout'])->name('orders.checkout');
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
});

require __DIR__.'/shop.php';
