<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_api_products_list_returns_json(): void
    {
        $response = $this->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'brand', 'price', 'category', 'gender', 'rating', 'reviews', 'image', 'colors', 'sizes']
            ],
            'total',
        ]);
    }

    public function test_api_products_filters_by_category_and_gender(): void
    {
        $response = $this->getJson('/api/products?category=1'); // Top
        $response->assertOk();
        $names = collect($response->json('data'))->pluck('name');
        $this->assertTrue($names->contains('เสื้อยืดคอกลม Basic'));
        $this->assertTrue($names->contains('Hoodie Oversize'));
        $this->assertFalse($names->contains('รองเท้าวิ่ง Swift'));

        $femaleResponse = $this->getJson('/api/products?gender=female');
        $femaleResponse->assertOk();
        $femaleNames = collect($femaleResponse->json('data'))->pluck('name');
        $this->assertFalse($femaleNames->contains('รองเท้าวิ่ง Swift')); // male
    }

    public function test_api_product_detail_returns_variants(): void
    {
        $response = $this->getJson('/api/products/1');

        $response->assertOk();
        $response->assertJsonPath('name', 'เสื้อยืดคอกลม Basic');
        $response->assertJsonStructure([
            'id', 'name', 'description', 'brand', 'price', 'variants' => [
                '*' => ['variant_id', 'color', 'size', 'sku', 'price', 'stock']
            ]
        ]);
    }

    public function test_seeded_catalog_supports_every_filter(): void
    {
        $products = collect($this->getJson('/api/products')->assertOk()->json('data'));

        $this->assertCount(20, $products);
        // Enough variety for the storefront filters to be meaningful
        $this->assertGreaterThanOrEqual(8, $products->pluck('category')->unique()->count());
        $this->assertCount(4, $products->pluck('brand')->unique());
        $this->assertEqualsCanonicalizing(['male', 'female', 'unisex'], $products->pluck('gender')->unique()->values()->all());

        // Product 20 is seeded sold out; in_stock lets the "In stock only" filter hide it
        $this->assertFalse($products->firstWhere('id', 20)['in_stock']);
        $this->assertTrue($products->firstWhere('id', 1)['in_stock']);

        $inStock = collect($this->getJson('/api/products?in_stock=1')->json('data'))->pluck('id');
        $this->assertNotContains(20, $inStock);

        $navy = collect($this->getJson('/api/products?color=Navy')->json('data'))->pluck('name');
        $this->assertContains('Polo Piqué Classic', $navy);
        $this->assertNotContains('Graphic Tee Bangkok', $navy);
    }
}
