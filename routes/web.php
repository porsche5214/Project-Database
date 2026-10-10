<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// ตัวอย่าง: ข้อมูลสินค้า (JSON) ไว้เช็คว่า DB ต่อได้ หน้า Blade จริงค่อยทำทีหลัง
Route::get('/api/products', [\App\Http\Controllers\ProductController::class, 'index']);
Route::get('/api/products/{id}', [\App\Http\Controllers\ProductController::class, 'show']);
