<?php

namespace Tests\Feature\API;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_can_create_order(): void
    {
        // Create a product first
        $product = Product::create([
            'name' => 'Test Product',
            'description' => 'Test product description',
            'price' => 10.99,
            'stock' => 100,
            'category' => 'Test',
            'active' => true,
        ]);

        // Test creating an order
        $response = $this->postJson('/api/orders', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 10.99,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'order' => [
                    'id',
                    'product_id',
                    'quantity',
                    'price',
                    'total',
                    'order_date',
                    'created_at',
                    'updated_at',
                ],
            ]);

        // Verify the order was created in the database
        $this->assertDatabaseHas('orders', [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 10.99,
            'total' => 21.98, // 10.99 * 2
        ]);
    }

    public function test_validation_fails_for_invalid_order(): void
    {
        // Test validation failure for missing required fields
        $response = $this->postJson('/api/orders', [
            // Missing product_id, quantity, and price
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id', 'quantity', 'price']);
    }
}
