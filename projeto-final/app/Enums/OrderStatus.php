<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Preparing = 'preparing';
    case OutForDelivery = 'out_for_delivery';
    case Delivered = 'delivered';

    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'Em preparação',
            self::OutForDelivery => 'Saiu para entrega',
            self::Delivered => 'Entregue',
        };
    }
}
