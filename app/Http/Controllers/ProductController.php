<?php

namespace App\Http\Controllers;

use App\Models\Product;

// ตัวอย่าง controller ให้ลอกแบบ: หน้า Products (public)
class ProductController extends Controller
{
    public function index()
    {
        return Product::with(['category', 'brand', 'mainImage'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function show(int $id)
    {
        return Product::with(['category', 'brand', 'images', 'variants', 'reviews.member:member_id,username'])
            ->withAvg('ratings', 'score')
            ->findOrFail($id);
    }
}
