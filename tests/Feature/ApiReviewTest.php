<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_review_or_rate(): void
    {
        $this->postJson('/api/products/1/reviews', ['comment' => 'Great!'])->assertStatus(401);
        $this->postJson('/api/products/1/rating', ['score' => 5])->assertStatus(401);
    }

    public function test_member_without_completed_order_cannot_review_or_rate(): void
    {
        $member = Member::factory()->create();

        // No order at all
        $this->actingAs($member)->postJson('/api/products/1/reviews', [
            'comment' => 'Trying to review',
        ])->assertStatus(403);

        $this->actingAs($member)->postJson('/api/products/1/rating', [
            'score' => 5,
        ])->assertStatus(403);

        // Order exists but status is 'paid' (not completed)
        $order = Order::create([
            'member_id' => $member->member_id,
            'contact_email' => $member->email,
            'ship_name' => 'Test',
            'ship_phone' => '123',
            'ship_address' => 'Addr',
            'total_amount' => 500,
            'status' => 'paid',
        ]);
        $variant = ProductVariant::where('product_id', 1)->first();
        OrderItem::insert([
            'order_id' => $order->order_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        $this->actingAs($member)->postJson('/api/products/1/reviews', [
            'comment' => 'Trying to review paid order',
        ])->assertStatus(403);
    }

    public function test_member_with_completed_order_can_review_and_rate(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::where('product_id', 1)->first();

        // Create completed order
        $order = Order::create([
            'member_id' => $member->member_id,
            'contact_email' => $member->email,
            'ship_name' => 'Verified Buyer',
            'ship_phone' => '0812345678',
            'ship_address' => 'Bangkok',
            'total_amount' => 500,
            'status' => 'completed',
        ]);
        OrderItem::insert([
            'order_id' => $order->order_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        // Post review with title and rating
        $response = $this->actingAs($member)->postJson('/api/products/1/reviews', [
            'title' => 'Excellent Fabric',
            'comment' => 'Super soft and fits perfectly!',
            'rating' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('review.title', 'Excellent Fabric')
            ->assertJsonPath('review.text', 'Super soft and fits perfectly!')
            ->assertJsonPath('review.rating', 5);

        // Verify stored in DB
        $this->assertDatabaseHas('review', [
            'member_id' => $member->member_id,
            'product_id' => 1,
            'comment' => '[Excellent Fabric] Super soft and fits perfectly!',
        ]);

        $this->assertDatabaseHas('rating', [
            'member_id' => $member->member_id,
            'product_id' => 1,
            'score' => 5,
        ]);
    }

    public function test_member_can_update_rating_score(): void
    {
        $member = Member::factory()->create();
        $variant = ProductVariant::where('product_id', 1)->first();

        $order = Order::create([
            'member_id' => $member->member_id,
            'contact_email' => $member->email,
            'ship_name' => 'Verified',
            'ship_phone' => '123',
            'ship_address' => 'Addr',
            'total_amount' => 500,
            'status' => 'completed',
        ]);
        OrderItem::insert([
            'order_id' => $order->order_id,
            'variant_id' => $variant->variant_id,
            'quantity' => 1,
            'unit_price' => 500,
        ]);

        // Submit rating 4
        $this->actingAs($member)->postJson('/api/products/1/rating', ['score' => 4])
            ->assertOk()
            ->assertJsonPath('score', 4);

        // Update rating to 5
        $this->actingAs($member)->postJson('/api/products/1/rating', ['score' => 5])
            ->assertOk()
            ->assertJsonPath('score', 5);

        $this->assertDatabaseHas('rating', [
            'member_id' => $member->member_id,
            'product_id' => 1,
            'score' => 5,
        ]);
    }

    public function test_public_can_view_product_reviews(): void
    {
        // Seeded review exists for product 3
        $response = $this->getJson('/api/products/3/reviews');
        $response->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('reviews.0.productId', 3);
    }
}
