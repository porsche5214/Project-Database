<?php

use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

// React Storefront (frontend/…/ build -> public/shop) is the only UI.
Route::get('/shop/{any?}', [ShopController::class, 'index'])->where('any', '.*')->name('shop');

// API routes with web middleware group (session + CSRF)
Route::prefix('api')->middleware('web')->group(base_path('routes/api.php'));

// Old Blade URLs now point to the matching React pages.
Route::redirect('/', '/shop');
Route::redirect('/products', '/shop/products');
Route::get('/products/{id}', fn (int $id) => redirect("/shop/products/{$id}"))->whereNumber('id');
Route::redirect('/wishlist', '/shop/wishlist');
Route::redirect('/cart', '/shop/cart');
Route::redirect('/checkout', '/shop/checkout');
Route::redirect('/orders', '/shop/account');
Route::get('/orders/{id}', fn () => redirect('/shop/account'))->whereNumber('id');
Route::redirect('/profile', '/shop/account')->name('profile');
Route::redirect('/register', '/shop/register')->name('register');
// Named "login" so Laravel's auth middleware can send guests here.
Route::redirect('/login', '/shop/login')->name('login');
