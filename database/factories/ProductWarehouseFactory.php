<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductWarehouseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => function () {
                return Product::all()->random()->id;
            },
            'warehouse_id' => function () {
                return Warehouse::all()->random()->id;
            },
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'quantity_pickable' => 0,
            'quantity_allocated' => 0,
            'quantity_allocated_pickable' => 0,
            'quantity_available' => 0,
            'quantity_to_replenish' => 0,
            'quantity_backordered' => 0,
            'quantity_sell_ahead' => 0,
            'quantity_inbound' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ];
    }
}
