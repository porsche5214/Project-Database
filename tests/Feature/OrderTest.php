<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

// Checkout / ประวัติ order ผ่าน API ที่หน้า React ใช้ (/api/checkout, /api/orders)
class OrderTest extends TestCase
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

    private function createAddress($memberId)
    {
        return Address::create([
            'member_id' => $memberId,
            'recipient_name' => 'John Doe',
            'phone' => '1234567890',
            'address_line' => '123 Main St',
            'province' => 'BKK',
            'postal_code' => '10000',
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

    public function test_checkout_decreases_stock_correct_price_clears_cart()
    {
        $member = $this->createMember();
        $address = $this->createAddress($member->member_id);
        $variant = $this->createVariant(10, 150);

        $this->actingAs($member);

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create([
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/checkout', [
            'address_id' => $address->address_id,
        ]);

        // Simulated store: orders are completed as soon as they are placed
        $response->assertCreated()->assertJsonPath('order.status', 'completed');

        $this->assertDatabaseEmpty('cart_item');

        // 2 x 150 = 300 + ค่าส่ง 150 (ยอดต่ำกว่า 2,500)
        $order = Order::where('member_id', $member->member_id)->first();
        $this->assertNotNull($order);
        $this->assertEquals(450, $order->total_amount);
        $this->assertSame('John Doe', $order->ship_name);

        $orderItem = OrderItem::where('order_id', $order->order_id)->first();
        $this->assertEquals(150, $orderItem->unit_price);
        $this->assertEquals(2, $orderItem->quantity);

        $updatedVariant = DB::table('product_variant')->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(8, $updatedVariant->stock);
    }

    public function test_member_can_review_right_after_checkout()
    {
        $member = $this->createMember();
        $address = $this->createAddress($member->member_id);
        $variant = $this->createVariant(10, 150);

        $this->actingAs($member)->postJson("/api/products/{$variant->product_id}/reviews", ['comment' => 'ยังไม่ได้ซื้อ'])
            ->assertForbidden();

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create(['cart_id' => $cart->cart_id, 'variant_id' => $variant->variant_id, 'quantity' => 1]);
        $this->actingAs($member)->postJson('/api/checkout', ['address_id' => $address->address_id])->assertCreated();

        $this->actingAs($member)->getJson("/api/products/{$variant->product_id}/eligibility")
            ->assertJsonPath('can_review', true);
        $this->actingAs($member)->postJson("/api/products/{$variant->product_id}/reviews", ['comment' => 'ได้ของแล้ว ดีมาก', 'rating' => 5])
            ->assertCreated();
    }

    public function test_checkout_rolls_back_if_stock_insufficient_and_keeps_cart()
    {
        $member = $this->createMember();
        $address = $this->createAddress($member->member_id);
        $variant = $this->createVariant(1, 150); // Only 1 in stock

        $this->actingAs($member);

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create([
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 2, // Try to buy 2
        ]);

        $response = $this->postJson('/api/checkout', [
            'address_id' => $address->address_id,
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('cart_item', [
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);
        $this->assertDatabaseCount('orders', 0);

        $updatedVariant = DB::table('product_variant')->where('variant_id', $variant->variant_id)->first();
        $this->assertEquals(1, $updatedVariant->stock);
    }

    public function test_cannot_checkout_with_another_members_address()
    {
        $member = $this->createMember();
        $other = $this->createMember();
        $otherAddress = $this->createAddress($other->member_id);
        $variant = $this->createVariant(10, 150);

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create(['cart_id' => $cart->cart_id, 'variant_id' => $variant->variant_id, 'quantity' => 1]);

        $this->actingAs($member)->postJson('/api/checkout', [
            'address_id' => $otherAddress->address_id,
        ])->assertStatus(422);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_cannot_view_others_orders()
    {
        $member1 = $this->createMember();
        $member2 = $this->createMember();

        $order = Order::create([
            'member_id' => $member1->member_id,
            'contact_email' => $member1->email,
            'ship_name' => 'Name',
            'ship_phone' => '123',
            'ship_address' => 'Addr',
            'total_amount' => 100,
        ]);

        $this->actingAs($member2)->getJson('/api/orders/' . $order->order_id)->assertStatus(404);
    }
}
