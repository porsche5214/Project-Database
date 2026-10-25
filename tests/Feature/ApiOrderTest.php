<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_orders_or_checkout(): void
    {
        $this->getJson('/api/orders')->assertStatus(401);
        $this->getJson('/api/orders/1')->assertStatus(401);
        $this->postJson('/api/checkout')->assertStatus(401);
    }

    public function test_cannot_checkout_with_empty_cart(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($member)->postJson('/api/checkout', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '0812345678',
            'address' => '123 Test Rd',
        ]);

        $response->assertStatus(422);
    }

    public function test_member_can_checkout_with_server_shipping_fee(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::where('stock', '>=', 5)->first();

        // Subtotal = 1 * price
        $price = (float) $variant->price;
        $expectedShipping = ($price < 2500) ? 150.0 : 0.0;
        $expectedTotal = $price + $expectedShipping;

        $initialStock = $variant->stock;

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create([
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($member)->postJson('/api/checkout', [
            'first_name' => 'Araya',
            'last_name' => 'Siriporn',
            'phone' => '0812345678',
            'address' => '88 Sukhumvit Road',
            'city' => 'Bangkok',
            'province' => 'Bangkok',
            'postal_code' => '10110',
            'save_address' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.ship_name', 'Araya Siriporn');
        // JSON drops ".0" on whole floats (440.0 -> 440), so compare by value
        $this->assertEquals($expectedTotal, $response->json('order.total_amount'));

        // Verify stock decremented
        $variant->refresh();
        $this->assertEquals($initialStock - 1, $variant->stock);

        // Verify this member's cart cleared (seeded member somchai still has items of their own)
        $this->assertDatabaseMissing('cart_item', ['cart_id' => $cart->cart_id]);

        // Verify address saved
        $this->assertDatabaseHas('address', [
            'member_id' => $member->member_id,
            'recipient_name' => 'Araya Siriporn',
        ]);
    }

    public function test_checkout_rolls_back_if_insufficient_stock(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::first();

        $initialStock = $variant->stock;

        $cart = Cart::create(['member_id' => $member->member_id]);
        CartItem::create([
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
            'quantity' => $initialStock + 5, // Exceed stock
        ]);

        $response = $this->actingAs($member)->postJson('/api/checkout', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'phone' => '0812345678',
            'address' => '123 Street',
        ]);

        $response->assertStatus(422);

        // Stock remains untouched
        $variant->refresh();
        $this->assertEquals($initialStock, $variant->stock);

        // Cart items preserved
        $this->assertDatabaseHas('cart_item', [
            'cart_id' => $cart->cart_id,
            'variant_id' => $variant->variant_id,
        ]);
    }

    public function test_member_can_view_order_history_and_detail(): void
    {
        // Member 2 (somchai) already has seeded order 1
        $somchai = Member::where('username', 'somchai')->first();

        $listResponse = $this->actingAs($somchai)->getJson('/api/orders');
        $listResponse->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.order_id', 1);

        $detailResponse = $this->actingAs($somchai)->getJson('/api/orders/1');
        $detailResponse->assertOk()
            ->assertJsonPath('order.order_id', 1)
            ->assertJsonCount(2, 'order.items');
        // Seeded order 1 has products 3 and 1; item order follows variant_id, so don't rely on position
        $this->assertEqualsCanonicalizing([1, 3], collect($detailResponse->json('order.items'))->pluck('product_id')->all());
    }

    public function test_member_cannot_view_another_members_order(): void
    {
        $otherMember = Member::factory()->create();

        // Order 1 belongs to somchai
        $response = $this->actingAs($otherMember)->getJson('/api/orders/1');
        $response->assertStatus(404);
    }

    private function createOrder(Member $member, string $status, int $quantity = 2): Order
    {
        $variant = ProductVariant::where('product_id', 1)->first();
        $order = Order::create([
            'member_id' => $member->member_id,
            'contact_email' => $member->email,
            'ship_name' => 'Test',
            'ship_phone' => '123',
            'ship_address' => 'Addr',
            'total_amount' => 500,
            'status' => $status,
        ]);
        OrderItem::insert([
            'order_id' => $order->order_id,
            'variant_id' => $variant->variant_id,
            'quantity' => $quantity,
            'unit_price' => 250,
        ]);

        return $order;
    }

    public function test_only_admin_can_update_order_status(): void
    {
        $member = Member::factory()->create();
        $admin = Member::where('username', 'admin')->first();
        $order = $this->createOrder($member, 'pending');

        $this->actingAs($member)->getJson('/api/admin/orders')->assertStatus(403);
        $this->actingAs($member)->patchJson("/api/admin/orders/{$order->order_id}/status", ['status' => 'paid'])
            ->assertStatus(403);

        $this->actingAs($admin)->getJson('/api/admin/orders')->assertOk();
        $this->actingAs($admin)->patchJson("/api/admin/orders/{$order->order_id}/status", ['status' => 'paid'])
            ->assertOk()
            ->assertJsonPath('order.status', 'paid');

        // Invalid transition: paid -> completed (must ship first)
        $this->actingAs($admin)->patchJson("/api/admin/orders/{$order->order_id}/status", ['status' => 'completed'])
            ->assertStatus(422);
    }

    public function test_cancelling_order_returns_stock(): void
    {
        $member = Member::factory()->create();
        $admin = Member::where('username', 'admin')->first();
        $order = $this->createOrder($member, 'paid', 3);
        $variant = ProductVariant::where('product_id', 1)->first();
        $stockBefore = $variant->stock;

        $this->actingAs($admin)->patchJson("/api/admin/orders/{$order->order_id}/status", ['status' => 'cancelled'])
            ->assertOk();

        $this->assertSame($stockBefore + 3, (int) $variant->fresh()->stock);
    }

    public function test_member_can_confirm_received_only_when_shipped(): void
    {
        $member = Member::factory()->create();
        $order = $this->createOrder($member, 'paid');

        $this->actingAs($member)->postJson("/api/orders/{$order->order_id}/received")->assertStatus(422);

        $order->update(['status' => 'shipped']);
        $this->actingAs($member)->postJson("/api/orders/{$order->order_id}/received")
            ->assertOk()
            ->assertJsonPath('order.status', 'completed');

        // Now the member is a verified buyer and can review
        $this->actingAs($member)->postJson('/api/products/1/reviews', ['comment' => 'Got it, great!'])
            ->assertStatus(201);

        // Other members cannot touch this order
        $other = Member::factory()->create();
        $this->actingAs($other)->postJson("/api/orders/{$order->order_id}/received")->assertStatus(404);
    }
}
