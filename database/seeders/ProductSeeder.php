<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Coffee',
                'description' => 'Premium brewed coffee',
                'price' => 3.99,
                'stock' => 100,
                'category' => 'Hot Drinks',
                'active' => true,
            ],
            [
                'name' => 'Iced Tea',
                'description' => 'Refreshing iced tea',
                'price' => 2.99,
                'stock' => 100,
                'category' => 'Cold Drinks',
                'active' => true,
            ],
            [
                'name' => 'Sandwich',
                'description' => 'Freshly made sandwich',
                'price' => 5.99,
                'stock' => 50,
                'category' => 'Food',
                'active' => true,
            ],
            [
                'name' => 'Salad',
                'description' => 'Healthy garden salad',
                'price' => 4.99,
                'stock' => 50,
                'category' => 'Food',
                'active' => true,
            ],
            [
                'name' => 'Juice',
                'description' => 'Fresh fruit juice',
                'price' => 3.49,
                'stock' => 80,
                'category' => 'Cold Drinks',
                'active' => true,
            ],
            [
                'name' => 'Hot Chocolate',
                'description' => 'Rich hot chocolate',
                'price' => 4.29,
                'stock' => 70,
                'category' => 'Hot Drinks',
                'active' => true,
            ],
            [
                'name' => 'Muffin',
                'description' => 'Freshly baked muffin',
                'price' => 2.49,
                'stock' => 60,
                'category' => 'Bakery',
                'active' => true,
            ],
            [
                'name' => 'Croissant',
                'description' => 'Buttery croissant',
                'price' => 2.29,
                'stock' => 60,
                'category' => 'Bakery',
                'active' => true,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
