<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// ข้อมูลตัวอย่างสำหรับ dev: php artisan migrate:fresh --seed
// login: admin@naemak.test / somchai@naemak.test  รหัส password123
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $pw = Hash::make('password123');

        DB::table('member')->insert([
            ['member_id' => 1, 'username' => 'admin', 'email' => 'admin@naemak.test', 'password_hash' => $pw, 'first_name' => 'Admin', 'last_name' => 'NaeMak', 'phone' => '0800000000', 'role' => 'admin'],
            ['member_id' => 2, 'username' => 'somchai', 'email' => 'somchai@naemak.test', 'password_hash' => $pw, 'first_name' => 'สมชาย', 'last_name' => 'ใจดี', 'phone' => '0811111111', 'role' => 'member'],
        ]);

        DB::table('address')->insert([
            'address_id' => 1, 'member_id' => 2, 'recipient_name' => 'สมชาย ใจดี', 'phone' => '0811111111', 'address_line' => '123/4 ถ.พหลโยธิน',
            'subdistrict' => 'สามเสนใน', 'district' => 'พญาไท', 'province' => 'กรุงเทพมหานคร', 'postal_code' => '10400', 'is_default' => true,
        ]);

        $categories = [
            [1, 'Top', null], [2, 'Bottom', null], [3, 'Socks', null], [4, 'Shoes', null],
            [5, 'T-shirt', 1], [6, 'Shirt', 1], [7, 'Sweater', 1], [8, 'Hoodie', 1], [9, 'Long-Sleeve', 1], [10, 'Polo', 1],
            [11, 'Pants', 2], [12, 'Skirt', 2], [13, 'Lifestyle', 4], [14, 'Sport', 4],
        ];
        foreach ($categories as [$id, $name, $parent]) {
            DB::table('category')->insert(['category_id' => $id, 'name' => $name, 'parent_category_id' => $parent]);
        }

        DB::table('brand')->insert([
            ['brand_id' => 1, 'name' => 'NaeMak Basic'],
            ['brand_id' => 2, 'name' => 'UrbanThai'],
            ['brand_id' => 3, 'name' => 'RunFast'],
        ]);

        DB::table('product')->insert([
            ['product_id' => 1, 'name' => 'เสื้อยืดคอกลม Basic', 'description' => 'ผ้าฝ้าย 100% ใส่สบาย', 'gender' => 'unisex', 'category_id' => 5, 'brand_id' => 1, 'base_price' => 290],
            ['product_id' => 2, 'name' => 'Hoodie Oversize', 'description' => 'ฮู้ดผ้าสำลี ทรงหลวม', 'gender' => 'unisex', 'category_id' => 8, 'brand_id' => 2, 'base_price' => 890],
            ['product_id' => 3, 'name' => 'รองเท้าวิ่ง Swift', 'description' => 'น้ำหนักเบา พื้นนุ่ม', 'gender' => 'male', 'category_id' => 14, 'brand_id' => 3, 'base_price' => 2490],
        ]);

        DB::table('product_image')->insert([
            ['image_id' => 1, 'product_id' => 1, 'image_url' => '/images/tee-white.jpg', 'is_main' => true],
            ['image_id' => 2, 'product_id' => 1, 'image_url' => '/images/tee-black.jpg', 'is_main' => false],
            ['image_id' => 3, 'product_id' => 2, 'image_url' => '/images/hoodie-grey.jpg', 'is_main' => true],
            ['image_id' => 4, 'product_id' => 3, 'image_url' => '/images/swift-blue.jpg', 'is_main' => true],
        ]);

        DB::table('product_variant')->insert([
            ['variant_id' => 1, 'product_id' => 1, 'color' => 'White', 'size' => 'M', 'sku' => 'TEE-WHT-M', 'price' => 290, 'stock' => 50],
            ['variant_id' => 2, 'product_id' => 1, 'color' => 'White', 'size' => 'L', 'sku' => 'TEE-WHT-L', 'price' => 290, 'stock' => 40],
            ['variant_id' => 3, 'product_id' => 1, 'color' => 'Black', 'size' => 'M', 'sku' => 'TEE-BLK-M', 'price' => 290, 'stock' => 30],
            ['variant_id' => 4, 'product_id' => 2, 'color' => 'Grey', 'size' => 'L', 'sku' => 'HOOD-GRY-L', 'price' => 890, 'stock' => 20],
            ['variant_id' => 5, 'product_id' => 3, 'color' => 'Blue', 'size' => '42', 'sku' => 'SWF-BLU-42', 'price' => 2490, 'stock' => 10],
        ]);

        DB::table('cart')->insert(['cart_id' => 1, 'member_id' => 2]);
        DB::table('cart_item')->insert([
            ['cart_id' => 1, 'variant_id' => 1, 'quantity' => 2],
            ['cart_id' => 1, 'variant_id' => 4, 'quantity' => 1],
        ]);

        DB::table('orders')->insert([
            'order_id' => 1, 'member_id' => 2, 'contact_email' => 'somchai@naemak.test', 'ship_name' => 'สมชาย ใจดี', 'ship_phone' => '0811111111',
            'ship_address' => '123/4 ถ.พหลโยธิน สามเสนใน พญาไท กรุงเทพมหานคร 10400', 'total_amount' => 2780, 'status' => 'completed',
        ]);
        DB::table('order_item')->insert([
            ['order_id' => 1, 'variant_id' => 5, 'quantity' => 1, 'unit_price' => 2490],
            ['order_id' => 1, 'variant_id' => 3, 'quantity' => 1, 'unit_price' => 290],
        ]);

        DB::table('wishlist')->insert(['member_id' => 2, 'product_id' => 2]);
        DB::table('rating')->insert(['member_id' => 2, 'product_id' => 3, 'score' => 5]);
        DB::table('review')->insert(['review_id' => 1, 'member_id' => 2, 'product_id' => 3, 'comment' => 'ใส่วิ่งสบายมาก']);
    }
}
