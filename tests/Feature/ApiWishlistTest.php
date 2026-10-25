<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApiWishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_wishlist_api(): void
    {
        $response = $this->getJson('/api/wishlist');
        $response->assertStatus(401);

        $addResponse = $this->postJson('/api/wishlist/1');
        $addResponse->assertStatus(401);
    }

    public function test_authenticated_user_can_view_empty_wishlist(): void
    {
        $member = Member::factory()->create();

        $response = $this->actingAs($member)->getJson('/api/wishlist');
        $response->assertOk()
            ->assertJson([
                'ids' => [],
                'products' => [],
            ]);
    }

    public function test_can_add_product_to_wishlist(): void
    {
        $member = Member::factory()->create();
        $product = Product::first();

        $response = $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}");
        $response->assertOk()
            ->assertJson([
                'ids' => [$product->product_id],
            ]);

        $this->assertDatabaseHas('wishlist', [
            'member_id' => $member->member_id,
            'product_id' => $product->product_id,
        ]);
    }

    public function test_adding_same_product_twice_is_idempotent(): void
    {
        $member = Member::factory()->create();
        $product = Product::first();

        $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}");
        $response = $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}");

        $response->assertOk();
        $this->assertSame(1, DB::table('wishlist')->where([
            'member_id' => $member->member_id,
            'product_id' => $product->product_id,
        ])->count());
    }

    public function test_can_remove_product_from_wishlist(): void
    {
        $member = Member::factory()->create();
        $product = Product::first();

        $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}");
        $response = $this->actingAs($member)->deleteJson("/api/wishlist/{$product->product_id}");

        $response->assertOk()
            ->assertJson([
                'ids' => [],
            ]);

        $this->assertDatabaseMissing('wishlist', [
            'member_id' => $member->member_id,
            'product_id' => $product->product_id,
        ]);
    }

    public function test_can_toggle_wishlist(): void
    {
        $member = Member::factory()->create();
        $product = Product::first();

        // Toggle on
        $toggleOn = $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}/toggle");
        $toggleOn->assertOk()
            ->assertJson([
                'in_wishlist' => true,
                'ids' => [$product->product_id],
            ]);

        // Toggle off
        $toggleOff = $this->actingAs($member)->postJson("/api/wishlist/{$product->product_id}/toggle");
        $toggleOff->assertOk()
            ->assertJson([
                'in_wishlist' => false,
                'ids' => [],
            ]);
    }
}
