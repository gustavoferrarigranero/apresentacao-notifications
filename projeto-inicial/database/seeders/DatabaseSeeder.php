<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $customer = User::factory()->create([
            'name' => 'Marina Cliente',
            'email' => 'cliente@example.com',
            'wants_email' => true,
            'wants_push' => false,
        ]);

        User::factory()->create([
            'name' => 'Operação Delivery',
            'email' => 'operacao@example.com',
            'wants_email' => false,
            'is_operations' => true,
            'slack_channel' => '#delivery-operations',
        ]);

        Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Preparing,
            'delivery_address' => 'Rua das Flores, 4821',
            'total_cents' => 7890,
        ]);
    }
}
