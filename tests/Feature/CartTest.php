<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// ตะกร้าผ่าน API ที่หน้า React ใช้ (/api/cart/*)
class CartTest extends TestCase
{
    use RefreshDatabase;

    private function createMember()
    {
        return Member::create([
            'username' => 'testuser' . uniqid(),
            'email' => uniqid() . '@example.com',
            'password_hash' => Hash::make('password123'),
        ]);
    }

    private function createVariant($stock = 10, $price = 100)
    {
        $catId = DB::table('category')->insertGetId([
            'name' => 'Test Category'
        ]);

        $prodId = DB::table('product')->insertGetId([
            'name' => 'Test Product',
            'category_id' => $catId,
            'base_price' => 100.00
        ]);

        $variantId = DB::table('product_variant')->insertGetId([
            'product_id' => $prodId,
            'color' => 'Red',
            'size' => 'M',
            'sku' => uniqid('SKU'),
            'price' => $price,
            'stock' => $stock
        ]);

        return DB::table('product_variant')->where('variant_id', $variantId)->first();
    }

    public function test_guest_cannot_access_cart()
    {
        $this->getJson('/api/cart')->assertStatus(401);
    }

    public function test_can_add_item_to_cart_and_quantity_is_added()
    {
        $variant = $this->createVariant();
        $member = $this->createMember();

        $this->actingAs($member);

        $this->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ])->assertOk();

        $cart = Cart::where('member_id', $member->member_id)->first();
        $this->assertNotNull($cart);

        $item = CartItem::where('cart_id', $cart->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(2, $item->quantity);

        $this->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 3,
        ])->assertOk()->assertJsonPath('cart.items.0.quantity', 5);

        $item = CartItem::where('cart_id', $cart->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(5, $item->quantity);
    }

    public function test_cannot_add_more_than_stock()
    {
        $variant = $this->createVariant(stock: 2);
        $member = $this->createMember();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 3,
        ])->assertStatus(422)->assertJsonValidationErrors('quantity');
    }

    public function test_unknown_color_or_size_is_rejected_not_swapped()
    {
        $variant = $this->createVariant(); // Red / M
        $member = $this->createMember();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'product_id' => $variant->product_id, 'color' => 'Purple', 'size' => 'XXXL', 'quantity' => 1,
        ])->assertStatus(422);
        $this->assertDatabaseCount('cart_item', 0);

        // The matching color/size still works
        $this->actingAs($member)->postJson('/api/cart/add', [
            'product_id' => $variant->product_id, 'color' => 'Red', 'size' => 'M', 'quantity' => 1,
        ])->assertOk()->assertJsonPath('cart.items.0.variant_id', $variant->variant_id);
    }

    public function test_cannot_modify_other_members_cart()
    {
        $variant = $this->createVariant();
        $member1 = $this->createMember();
        $member2 = $this->createMember();

        $this->actingAs($member1)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);

        $this->actingAs($member2)->postJson('/api/cart/remove/' . $variant->variant_id);

        $cart1 = Cart::where('member_id', $member1->member_id)->first();
        $item = CartItem::where('cart_id', $cart1->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertNotNull($item);
    }
}
