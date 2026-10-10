<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

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
        $response = $this->get('/cart');
        $response->assertRedirect('/login');
    }

    public function test_can_add_item_to_cart_and_quantity_is_added()
    {
        $variant = $this->createVariant();
        $member = $this->createMember();

        $this->actingAs($member);

        $response = $this->post('/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);
        $response->assertSessionHas('success');

        $cart = Cart::where('member_id', $member->member_id)->first();
        $this->assertNotNull($cart);
        
        $item = CartItem::where('cart_id', $cart->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(2, $item->quantity);

        $this->post('/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 3,
        ]);

        $item = CartItem::where('cart_id', $cart->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(5, $item->quantity);
    }

    public function test_cannot_modify_other_members_cart()
    {
        $variant = $this->createVariant();
        $member1 = $this->createMember();
        $member2 = $this->createMember();

        $this->actingAs($member1);
        $this->post('/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);

        $this->actingAs($member2);
        $this->post('/cart/remove/' . $variant->variant_id);

        $cart1 = Cart::where('member_id', $member1->member_id)->first();
        $item = CartItem::where('cart_id', $cart1->cart_id)->where('variant_id', $variant->variant_id)->first();
        $this->assertNotNull($item); 
    }
}
