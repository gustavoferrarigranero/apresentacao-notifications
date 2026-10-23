<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'status' => OrderStatus::Preparing,
            'delivery_address' => fake()->address(),
            'total_cents' => fake()->numberBetween(2500, 15000),
        ];
    }
}
