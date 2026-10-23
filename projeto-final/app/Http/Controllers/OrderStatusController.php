<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Http\RedirectResponse;

final class OrderStatusController extends Controller
{
    public function __invoke(UpdateOrderStatusRequest $request, Order $order, OrderStatusService $service): RedirectResponse
    {
        $service->change($order, OrderStatus::from($request->validated('status')));

        return back()->with('status', 'Status atualizado e notificações enfileiradas.');
    }
}
