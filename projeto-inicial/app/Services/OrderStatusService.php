<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;

final class OrderStatusService
{
    public function change(Order $order, OrderStatus $status): Order
    {
        return $order;
    }
}
