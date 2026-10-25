<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_me_returns_null_for_guest(): void
    {
        $response = $this->get('/api/me');

        $response->assertOk();
        $this->assertSame('null', $response->getContent());
        $this->assertNull(json_decode($response->getContent(), true));
    }

    public function test_api_me_returns_user_when_authenticated(): void
    {
        $member = Member::create([
            'username' => 'somchai',
            'email' => 'somchai@naemak.test',
            'password_hash' => bcrypt('password123'),
            'role' => 'member',
        ]);

        $response = $this->actingAs($member)->get('/api/me');

        $response->assertOk();
        $this->assertSame('somchai', $response->json('username'));
        $this->assertSame('somchai@naemak.test', $response->json('email'));
    }

    public function test_shop_serves_spa_shell(): void
    {
        $response = $this->get('/shop');

        $response->assertOk();
        $response->assertSee('<div id="root">', false);
    }

    public function test_shop_subroutes_serve_spa_shell(): void
    {
        $response = $this->get('/shop/products');

        $response->assertOk();
        $response->assertSee('<div id="root">', false);
    }

    public function test_old_blade_urls_redirect_to_react_pages(): void
    {
        $this->get('/products')->assertRedirect('/shop/products');
        $this->get('/products/3')->assertRedirect('/shop/products/3');
        $this->get('/wishlist')->assertRedirect('/shop/wishlist');
        $this->get('/cart')->assertRedirect('/shop/cart');
        $this->get('/checkout')->assertRedirect('/shop/checkout');
        $this->get('/orders')->assertRedirect('/shop/account');
        $this->get('/orders/1')->assertRedirect('/shop/account');
        $this->get('/profile')->assertRedirect('/shop/account');
        $this->get('/login')->assertRedirect('/shop/login');
        $this->get('/register')->assertRedirect('/shop/register');
    }
}
