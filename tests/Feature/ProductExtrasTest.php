<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// ทดสอบสินค้า + Wishlist / Rating / Review ผ่าน API ที่หน้า React ใช้ ด้วยข้อมูลจาก DatabaseSeeder
class ProductExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // seed ทุกเทส: RefreshDatabase seed ให้เฉพาะครั้งแรกที่ migrate ถ้าเทสอื่นรันก่อนจะไม่มีข้อมูล
        $this->seed();
    }

    private function somchai(): Member
    {
        return Member::where('username', 'somchai')->firstOrFail();
    }

    // สร้าง order สถานะ completed ให้ member ซื้อสินค้านี้แล้ว (มีสิทธิ์รีวิว/ให้คะแนน)
    private function completedOrderFor(Member $m, int $productId): void
    {
        $orderId = DB::table('orders')->insertGetId([
            'member_id' => $m->member_id, 'contact_email' => $m->email, 'ship_name' => 'Test',
            'ship_phone' => '0800000000', 'ship_address' => 'Bangkok', 'total_amount' => 290, 'status' => 'completed',
        ]);
        $variantId = DB::table('product_variant')->where('product_id', $productId)->value('variant_id');
        DB::table('order_item')->insert(['order_id' => $orderId, 'variant_id' => $variantId, 'quantity' => 1, 'unit_price' => 290]);
    }

    private function productNames(string $url): array
    {
        return collect($this->getJson($url)->assertOk()->json('data'))->pluck('name')->all();
    }

    public function test_product_list_filters_by_category_brand_and_gender(): void
    {
        $all = $this->productNames('/api/products');
        $this->assertContains('Hoodie Oversize', $all);
        $this->assertContains('รองเท้าวิ่ง Swift', $all);

        // หมวดแม่ Top (1) ต้องรวมหมวดลูก T-shirt / Hoodie
        $top = $this->productNames('/api/products?category=1');
        $this->assertContains('เสื้อยืดคอกลม Basic', $top);
        $this->assertContains('Hoodie Oversize', $top);
        $this->assertNotContains('รองเท้าวิ่ง Swift', $top);

        $brand = $this->productNames('/api/products?brand=3');
        $this->assertContains('รองเท้าวิ่ง Swift', $brand);
        $this->assertNotContains('Hoodie Oversize', $brand);

        // female: มีแต่ unisex ไม่มีรองเท้า male
        $female = $this->productNames('/api/products?gender=female');
        $this->assertContains('Hoodie Oversize', $female);
        $this->assertNotContains('รองเท้าวิ่ง Swift', $female);
    }

    public function test_product_detail_shows_variants_and_average_rating(): void
    {
        $response = $this->getJson('/api/products/3')->assertOk()
            ->assertJsonPath('name', 'รองเท้าวิ่ง Swift')
            ->assertJsonPath('variants.0.size', '42')
            ->assertJsonPath('reviews_list.0.text', 'ใส่วิ่งสบายมาก');
        $this->assertEquals(5, $response->json('rating'));

        $this->getJson('/api/products/999')->assertNotFound();
    }

    public function test_wishlist_add_and_remove(): void
    {
        $m = $this->somchai();

        $this->actingAs($m)->postJson('/api/wishlist/1')->assertOk();
        $this->actingAs($m)->postJson('/api/wishlist/1')->assertOk(); // ซ้ำต้องไม่ error
        $this->assertSame(1, DB::table('wishlist')->where(['member_id' => $m->member_id, 'product_id' => 1])->count());

        $names = collect($this->actingAs($m)->getJson('/api/wishlist')->assertOk()->json('products'))->pluck('name');
        $this->assertContains('เสื้อยืดคอกลม Basic', $names);

        $this->actingAs($m)->deleteJson('/api/wishlist/1')->assertOk();
        $this->assertDatabaseMissing('wishlist', ['member_id' => $m->member_id, 'product_id' => 1]);
    }

    public function test_rating_is_one_per_member_and_validated(): void
    {
        $m = $this->somchai();
        $admin = Member::where('username', 'admin')->firstOrFail();

        // admin ยังไม่เคยซื้อ -> ให้คะแนนไม่ได้
        $this->actingAs($admin)->postJson('/api/products/1/rating', ['score' => 5])->assertForbidden();
        $this->completedOrderFor($admin, 1);

        // somchai มี order 1 (completed) ที่มีสินค้า 1 จาก seeder อยู่แล้ว
        $this->actingAs($m)->postJson('/api/products/1/rating', ['score' => 6])->assertJsonValidationErrors('score');
        $this->actingAs($m)->postJson('/api/products/1/rating', ['score' => 2])->assertOk();
        $this->actingAs($m)->postJson('/api/products/1/rating', ['score' => 4])->assertOk(); // ให้ใหม่ = แก้คะแนนเดิม
        $this->actingAs($admin)->postJson('/api/products/1/rating', ['score' => 5])
            ->assertOk()
            ->assertJsonPath('average_rating', 4.5)
            ->assertJsonPath('ratings_count', 2);

        $this->assertSame(2, DB::table('rating')->where('product_id', 1)->count());
        $this->getJson('/api/products/1')->assertJsonPath('rating', 4.5);
    }

    public function test_review_create_and_only_owner_or_admin_can_delete(): void
    {
        $m = $this->somchai();
        $admin = Member::where('username', 'admin')->firstOrFail();

        $this->actingAs($m)->postJson('/api/products/1/reviews', ['comment' => ''])->assertStatus(422);
        $this->actingAs($m)->postJson('/api/products/1/reviews', ['comment' => 'ผ้าดีมาก'])->assertCreated();
        $id = DB::table('review')->where('comment', 'ผ้าดีมาก')->value('review_id');

        $other = Member::create(['username' => 'other', 'email' => 'other@naemak.test', 'password_hash' => 'x']);
        $this->actingAs($other)->postJson('/api/products/1/reviews', ['comment' => 'ยังไม่ได้ซื้อ'])->assertForbidden();
        $this->actingAs($other)->deleteJson("/api/reviews/$id")->assertForbidden();

        $this->actingAs($admin)->deleteJson("/api/reviews/$id")->assertOk();
        $this->assertDatabaseMissing('review', ['review_id' => $id]);
    }
}
