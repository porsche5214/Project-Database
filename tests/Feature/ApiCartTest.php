<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Member;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_cart_api(): void
    {
        $response = $this->getJson('/api/cart');
        $response->assertStatus(401);

        $addResponse = $this->postJson('/api/cart/add', [
            'variant_id' => 1,
            'quantity' => 1,
        ]);
        $addResponse->assertStatus(401);
    }

    public function test_authenticated_user_can_view_empty_cart(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($member)->getJson('/api/cart');
        $response->assertOk()
            ->assertJson([
                'items' => [],
                'subtotal' => 0,
                'shipping' => 0,
                'total' => 0,
            ]);
    }

    public function test_can_add_item_to_cart_and_calculates_shipping(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::first();

        // Add 1 item
        $response = $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('cart.items.0.variant_id', $variant->variant_id)
            ->assertJsonPath('cart.items.0.quantity', 1);

        $price = (float) $variant->price;
        $expectedShipping = ($price < 2500) ? 150.0 : 0.0;
        $expectedTotal = $price + $expectedShipping;

        // JSON drops ".0" on whole floats (290.0 -> 290), so compare by value
        $this->assertEquals($price, $response->json('cart.subtotal'));
        $this->assertEquals($expectedShipping, $response->json('cart.shipping'));
        $this->assertEquals($expectedTotal, $response->json('cart.total'));
    }

    public function test_adding_existing_variant_increments_quantity(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::where('stock', '>=', 5)->first();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 2,
        ]);

        $response = $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('cart.items.0.quantity', 3);
    }

    public function test_cannot_add_more_than_available_stock(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::first();

        $response = $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => $variant->stock + 10,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_can_update_quantity_in_cart(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::where('stock', '>=', 5)->first();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($member)->postJson("/api/cart/update/{$variant->variant_id}", [
            'quantity' => 3,
        ]);

        $response->assertOk()
            ->assertJsonPath('cart.items.0.quantity', 3);
    }

    public function test_can_remove_item_from_cart(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::first();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($member)->postJson("/api/cart/remove/{$variant->variant_id}");
        $response->assertOk()
            ->assertJsonPath('cart.items', []);
    }

    public function test_can_clear_cart(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::first();

        $this->actingAs($member)->postJson('/api/cart/add', [
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
        ]);

        $response = $this->actingAs($member)->postJson('/api/cart/clear');
        $response->assertOk()
            ->assertJsonPath('cart.items', []);
    }
}
