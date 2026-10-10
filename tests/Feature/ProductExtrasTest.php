<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// ทดสอบหน้าสินค้า + Wishlist / Rating / Review ด้วยข้อมูลจาก DatabaseSeeder
class ProductExtrasTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    private function somchai(): Member
    {
        return Member::where('username', 'somchai')->firstOrFail();
    }

    public function test_product_list_filters_by_category_brand_and_gender(): void
    {
        $this->get('/products')->assertOk()->assertSee('Hoodie Oversize')->assertSee('รองเท้าวิ่ง Swift');

        // หมวดแม่ Top (1) ต้องรวมหมวดลูก T-shirt / Hoodie
        $this->get('/products?category=1')->assertOk()
            ->assertSee('เสื้อยืดคอกลม Basic')->assertSee('Hoodie Oversize')->assertDontSee('รองเท้าวิ่ง Swift');

        $this->get('/products?brand=3')->assertOk()
            ->assertSee('รองเท้าวิ่ง Swift')->assertDontSee('Hoodie Oversize');

        // female: มีแต่ unisex ไม่มีรองเท้า male
        $this->get('/products?gender=female')->assertOk()
            ->assertSee('Hoodie Oversize')->assertDontSee('รองเท้าวิ่ง Swift');
    }

    public function test_product_detail_shows_variants_and_average_rating(): void
    {
        $this->get('/products/3')->assertOk()
            ->assertSee('รองเท้าวิ่ง Swift')
            ->assertSee('"size":"42"', false) // variants ส่งไปให้ JS เลือกสี/ไซซ์
            ->assertSee('5.0')
            ->assertSee('ใส่วิ่งสบายมาก');

        $this->get('/products/999')->assertNotFound();
    }

    public function test_wishlist_add_and_remove(): void
    {
        $m = $this->somchai();

        $this->actingAs($m)->post('/wishlist/1')->assertRedirect();
        $this->actingAs($m)->post('/wishlist/1')->assertRedirect(); // ซ้ำต้องไม่ error
        $this->assertSame(1, DB::table('wishlist')->where(['member_id' => $m->member_id, 'product_id' => 1])->count());

        $this->actingAs($m)->get('/wishlist')->assertOk()->assertSee('เสื้อยืดคอกลม Basic');

        $this->actingAs($m)->delete('/wishlist/1')->assertRedirect();
        $this->assertDatabaseMissing('wishlist', ['member_id' => $m->member_id, 'product_id' => 1]);
    }

    public function test_rating_is_one_per_member_and_validated(): void
    {
        $m = $this->somchai();
        $admin = Member::where('username', 'admin')->firstOrFail();

        $this->actingAs($m)->post('/products/1/rating', ['score' => 6])->assertSessionHasErrors('score');
        $this->actingAs($m)->post('/products/1/rating', ['score' => 2]);
        $this->actingAs($m)->post('/products/1/rating', ['score' => 4]); // ให้ใหม่ = แก้คะแนนเดิม
        $this->actingAs($admin)->post('/products/1/rating', ['score' => 5]);

        $this->assertSame(2, DB::table('rating')->where('product_id', 1)->count());
        $this->assertEquals(4.5, DB::table('rating')->where('product_id', 1)->avg('score'));
        $this->get('/products/1')->assertSee('4.5');
    }

    public function test_review_create_and_only_owner_or_admin_can_delete(): void
    {
        $m = $this->somchai();
        $admin = Member::where('username', 'admin')->firstOrFail();

        $this->actingAs($m)->post('/products/1/reviews', ['comment' => ''])->assertSessionHasErrors('comment');
        $this->actingAs($m)->post('/products/1/reviews', ['comment' => 'ผ้าดีมาก']);
        $id = DB::table('review')->where('comment', 'ผ้าดีมาก')->value('review_id');

        $other = Member::create(['username' => 'other', 'email' => 'other@naemak.test', 'password_hash' => 'x']);
        $this->actingAs($other)->delete("/reviews/$id")->assertForbidden();

        $this->actingAs($admin)->delete("/reviews/$id")->assertRedirect();
        $this->assertDatabaseMissing('review', ['review_id' => $id]);
    }
}
