<?php

use App\Http\Controllers\NotificationCenterController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\PushSubscriptionController;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('dashboard', [
    'orders' => Order::query()->with('customer')->latest()->get(),
    'users' => User::query()->orderBy('name')->get(),
]))->name('dashboard');

Route::get('/orders/{order}', fn (Order $order) => view('orders.show', [
    'order' => $order->load('customer'),
]))->name('orders.show');

Route::patch('/orders/{order}/status', OrderStatusController::class)->name('orders.status');
Route::get('/users/{user}/notifications', [NotificationCenterController::class, 'index'])->name('notifications.index');
Route::patch('/users/{user}/notifications/{notification}/read', [NotificationCenterController::class, 'read'])->name('notifications.read');
Route::post('/users/{user}/push-subscriptions', PushSubscriptionController::class)->name('push-subscriptions.store');

Route::get('/api/health', fn () => response()->json([
    'status' => 'ok',
    'channels' => ['database', 'mail', 'slack', 'webpush'],
]));
