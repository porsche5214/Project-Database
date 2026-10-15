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
}
