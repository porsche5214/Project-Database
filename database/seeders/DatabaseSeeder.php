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
            ['brand_id' => 4, 'name' => 'NaeMak Studio'],
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

        $this->seedCatalog();

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

    // สินค้าเพิ่มเติมให้หน้าเว็บมีของโชว์และลอง filter ได้ (หมวด / แบรนด์ / เพศ / ไซซ์ / สี / ราคา / มีสต็อก)
    // variant สร้างจาก สี x ไซซ์ ทุกคู่ ราคา = base_price
    private function seedCatalog(): void
    {
        $clothing = ['XS', 'S', 'M', 'L', 'XL'];

        // [id, ชื่อ, คำอธิบาย, เพศ, category_id, brand_id, ราคา, รูป, สี, ไซซ์, สต็อกต่อ variant]
        $products = [
            [4, 'Oxford Shirt Relaxed', 'เชิ้ต Oxford ทรงหลวม ใส่ทำงานหรือเที่ยวก็ได้', 'male', 6, 4, 1290, 'look-5.jpg', ['White', 'Stone'], ['S', 'M', 'L', 'XL'], 15],
            [5, 'Linen Shirt Short Sleeve', 'เชิ้ตลินินแขนสั้น ระบายอากาศดี', 'unisex', 6, 2, 990, 'look-6.jpg', ['Cream', 'Olive'], ['S', 'M', 'L'], 12],
            [6, 'Crewneck Sweater Knit', 'สเวตเตอร์ไหมพรมคอกลม เนื้อนุ่ม', 'female', 7, 4, 1590, 'look-7.jpg', ['Cream', 'Charcoal'], ['XS', 'S', 'M', 'L'], 8],
            [7, 'Graphic Tee Bangkok', 'เสื้อยืดสกรีนลาย Bangkok', 'unisex', 5, 2, 390, 'tee-black.jpg', ['Black', 'White'], ['S', 'M', 'L', 'XL'], 40],
            [8, 'Zip Hoodie Tech', 'ฮู้ดซิปผ้าเทค กันลมเบา ๆ', 'male', 8, 3, 1490, 'hoodie-grey.jpg', ['Charcoal', 'Navy'], ['M', 'L', 'XL'], 10],
            [9, 'Long-Sleeve Tee Rib', 'เสื้อแขนยาวผ้าร่อง ทรงเข้ารูป', 'female', 9, 1, 450, 'look-8.jpg', ['Black', 'Stone'], ['XS', 'S', 'M', 'L'], 25],
            [10, 'Polo Piqué Classic', 'โปโลผ้าปิเก้ ทรงคลาสสิก', 'male', 10, 1, 690, 'tee-white.jpg', ['Navy', 'White'], $clothing, 20],
            [11, 'Wide-Leg Trousers', 'กางเกงขากว้าง เอวสูง', 'female', 11, 4, 1390, 'look-6.jpg', ['Black', 'Beige'], ['XS', 'S', 'M', 'L'], 10],
            [12, 'Cargo Pants Utility', 'กางเกงคาร์โก้ กระเป๋าข้าง', 'male', 11, 2, 1190, 'swift-blue.jpg', ['Olive', 'Black'], ['S', 'M', 'L', 'XL'], 14],
            [13, 'Jogger Pants Fleece', 'จ็อกเกอร์ผ้าสำลี ใส่สบาย', 'unisex', 11, 3, 890, 'hoodie-grey.jpg', ['Grey', 'Black'], $clothing, 18],
            [14, 'Pleated Midi Skirt', 'กระโปรงพลีทยาวคลุมเข่า', 'female', 12, 4, 1190, 'look-7.jpg', ['Black', 'Stone'], ['XS', 'S', 'M'], 9],
            [15, 'Ankle Socks 3-Pack', 'ถุงเท้าข้อสั้น แพ็ก 3 คู่', 'unisex', 3, 1, 190, 'tee-black.jpg', ['White', 'Black'], ['Free'], 60],
            [16, 'Crew Socks Sport', 'ถุงเท้ากีฬาข้อกลาง พื้นหนา', 'unisex', 3, 3, 250, 'swift-blue.jpg', ['White', 'Grey'], ['Free'], 45],
            [17, 'Canvas Sneakers Low', 'รองเท้าผ้าใบหุ้มข้อต่ำ', 'unisex', 13, 1, 1290, 'tee-white.jpg', ['White', 'Black'], ['39', '40', '41', '42', '43'], 6],
            [18, 'Leather Loafers', 'โลฟเฟอร์หนังแท้', 'female', 13, 4, 3290, 'look-8.jpg', ['Black', 'Brown'], ['36', '37', '38', '39'], 4],
            [19, 'Trail Runner Pro', 'รองเท้าวิ่งเทรล พื้นยึดเกาะ', 'male', 14, 3, 3890, 'look-5.jpg', ['Black', 'Olive'], ['40', '41', '42', '43', '44'], 5],
            // ของหมด ไว้ลอง filter "มีสต็อก"
            [20, 'Sweatshirt Heavyweight', 'สเวตเชิ้ตผ้าหนา (สินค้าหมด)', 'unisex', 7, 2, 990, 'hoodie-grey.jpg', ['Grey', 'Navy'], ['S', 'M', 'L'], 0],
        ];

        foreach ($products as [$id, $name, $description, $gender, $categoryId, $brandId, $price, $image, $colors, $sizes, $stock]) {
            DB::table('product')->insert([
                'product_id' => $id, 'name' => $name, 'description' => $description, 'gender' => $gender,
                'category_id' => $categoryId, 'brand_id' => $brandId, 'base_price' => $price,
            ]);
            DB::table('product_image')->insert(['product_id' => $id, 'image_url' => "/images/$image", 'is_main' => true]);

            foreach ($colors as $c => $color) {
                foreach ($sizes as $s => $size) {
                    DB::table('product_variant')->insert([
                        'product_id' => $id, 'color' => $color, 'size' => $size,
                        'sku' => sprintf('P%02d-%s-%s', $id, strtoupper(substr($color, 0, 3)), $size),
                        'price' => $price,
                        // บาง variant หมด ให้หน้าเว็บมีทั้งของมีและของหมด
                        'stock' => ($c + $s) % 4 === 3 ? 0 : $stock,
                    ]);
                }
            }
        }

        // คะแนนตัวอย่าง ให้เรียงตาม "Highest Rated" ได้ (member 1 = admin, 2 = somchai)
        foreach ([4 => [5, 4], 6 => [5, 5], 7 => [4, 4], 10 => [3, 4], 12 => [4, 5], 17 => [5, 4], 19 => [5, 5]] as $productId => [$admin, $somchai]) {
            DB::table('rating')->insert([
                ['member_id' => 1, 'product_id' => $productId, 'score' => $admin],
                ['member_id' => 2, 'product_id' => $productId, 'score' => $somchai],
            ]);
        }
    }
}
