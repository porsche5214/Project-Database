<?php

// Routes ของคน B: สินค้า, Wishlist, Review, Rating
// (Cart / login / checkout อยู่ฝั่งคน A)

use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('products.show');

Route::middleware('auth')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{id}', [WishlistController::class, 'store'])->whereNumber('id')->name('wishlist.store');
    Route::delete('/wishlist/{id}', [WishlistController::class, 'destroy'])->whereNumber('id')->name('wishlist.destroy');

    Route::post('/products/{id}/rating', [RatingController::class, 'store'])->whereNumber('id')->name('ratings.store');

    Route::post('/products/{id}/reviews', [ReviewController::class, 'store'])->whereNumber('id')->name('reviews.store');
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->whereNumber('id')->name('reviews.destroy');
});

// ไว้ทดสอบตอนที่หน้า login ของคน A ยังไม่เสร็จ: เปิด /dev/login-as/2 = login เป็น somchai
// ใช้ได้เฉพาะ APP_ENV=local ลบทิ้งได้เมื่อมี login จริง
if (app()->environment('local')) {
    Route::get('/dev/login-as/{id}', function (int $id) {
        Auth::login(Member::findOrFail($id));
        return redirect()->route('products.index');
    })->whereNumber('id');
}
