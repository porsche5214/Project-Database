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
            ->assertJsonPath('order.total_amount', $expectedTotal)
            ->assertJsonPath('order.ship_name', 'Araya Siriporn');

        // Verify stock decremented
        $variant->refresh();
        $this->assertEquals($initialStock - 1, $variant->stock);

        // Verify cart cleared
        $this->assertDatabaseEmpty('cart_item');

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
            ->assertJsonPath('order.items.0.product_id', 3);
    }

    public function test_member_cannot_view_another_members_order(): void
    {
        $otherMember = Member::factory()->create();

        // Order 1 belongs to somchai
        $response = $this->actingAs($otherMember)->getJson('/api/orders/1');
        $response->assertStatus(404);
    }
}
