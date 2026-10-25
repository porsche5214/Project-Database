<?php

use App\Http\Controllers\Api\AdminOrderController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\RatingController;
use Illuminate\Support\Facades\Route;

// Guest Auth
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Session / Current user (can be guest or member)
Route::get('/me', [AuthController::class, 'me']);

// Authenticated Auth & Profile
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::match(['put', 'patch', 'post'], '/profile', [ProfileController::class, 'update']);
    Route::post('/profile/address', [ProfileController::class, 'saveAddress']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/add', [CartController::class, 'add']);
    Route::post('/cart/update/{variant_id}', [CartController::class, 'update']);
    Route::post('/cart/remove/{variant_id}', [CartController::class, 'remove']);
    Route::post('/cart/clear', [CartController::class, 'clear']);

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist/{id}', [WishlistController::class, 'store'])->whereNumber('id');
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])->whereNumber('id');
    Route::post('/wishlist/{id}/toggle', [WishlistController::class, 'toggle'])->whereNumber('id');

    // Orders & Checkout
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');
    Route::post('/checkout', [OrderController::class, 'checkout']);
    Route::post('/orders/{id}/received', [OrderController::class, 'confirmReceived'])->whereNumber('id');

    // Admin: order management
    Route::get('/admin/orders', [AdminOrderController::class, 'index']);
    Route::patch('/admin/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->whereNumber('id');

    // Reviews & Ratings (Auth)
    Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])->whereNumber('id');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->whereNumber('id');
    Route::get('/products/{id}/eligibility', [ReviewController::class, 'eligibility'])->whereNumber('id');
    Route::post('/products/{id}/rating', [RatingController::class, 'store'])->whereNumber('id');
    Route::delete('/products/{id}/rating', [RatingController::class, 'destroy'])->whereNumber('id');



});

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');

Route::get('/products/{id}/reviews', [ReviewController::class, 'index'])->whereNumber('id');

