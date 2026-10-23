<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class OrderStatusService
{
    public function change(Order $order, OrderStatus $status): Order
    {
        return DB::transaction(function () use ($order, $status): Order {
            $order->update(['status' => $status]);
            $order->refresh()->load('customer');
            $order->customer->notify(new OrderStatusChanged($order));
            Notification::send(
                User::query()->where('is_operations', true)->get(),
                new OrderStatusChanged($order),
            );

            return $order;
        });
    }
}
