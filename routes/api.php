<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\ProductController;
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
});

// Products
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
