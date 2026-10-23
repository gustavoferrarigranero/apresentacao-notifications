<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use Tests\TestCase;

final class MultichannelNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_lists_all_supported_channels(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('channels.0', 'database')
            ->assertJsonPath('channels.3', 'webpush');
    }

    public function test_customer_channels_respect_preferences_and_subscriptions(): void
    {
        $customer = User::factory()->create(['wants_email' => true, 'wants_push' => true]);
        $customer->updatePushSubscription('https://push.example.test/device', 'public-key', 'auth-token');
        $order = Order::factory()->for($customer, 'customer')->create(['status' => OrderStatus::OutForDelivery]);

        $channels = (new OrderStatusChanged($order))->via($customer);

        $this->assertSame(['database', 'mail', WebPushChannel::class], $channels);
    }

    public function test_operations_user_receives_database_and_slack(): void
    {
        $operator = User::factory()->create(['wants_email' => false, 'is_operations' => true]);
        $order = Order::factory()->create(['status' => OrderStatus::OutForDelivery]);

        $this->assertSame(['database', 'slack'], (new OrderStatusChanged($order))->via($operator));
    }

    public function test_status_service_changes_order_and_notifies_customer_and_operations(): void
    {
        Notification::fake();
        $customer = User::factory()->create(['wants_email' => true]);
        $operator = User::factory()->create(['is_operations' => true]);
        $order = Order::factory()->for($customer, 'customer')->create();

        app(OrderStatusService::class)->change($order, OrderStatus::OutForDelivery);

        $this->assertSame(OrderStatus::OutForDelivery, $order->fresh()->status);
        Notification::assertSentTo([$customer, $operator], OrderStatusChanged::class);
    }

    public function test_each_representation_contains_the_order_context(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->for($customer, 'customer')->create(['status' => OrderStatus::OutForDelivery]);
        $notification = new OrderStatusChanged($order->load('customer'));

        $this->assertSame($order->id, $notification->toDatabase($customer)['order_id']);
        $this->assertSame('Pedido a caminho', $notification->toMail($customer)->subject);
        $this->assertSame('Pedido saiu para entrega', $notification->toSlack($customer)->toArray()['text']);
        $this->assertSame($order->id, $notification->toWebPush($customer, $notification)->toArray()['data']['order_id']);
    }

    public function test_database_notification_can_be_read_from_the_owner_center(): void
    {
        $customer = User::factory()->create(['wants_email' => false]);
        $order = Order::factory()->for($customer, 'customer')->create(['status' => OrderStatus::OutForDelivery]);
        $customer->notifyNow(new OrderStatusChanged($order), ['database']);
        $stored = $customer->notifications()->firstOrFail();

        $this->get(route('notifications.index', $customer))
            ->assertOk()
            ->assertSee("O pedido #{$order->id}");

        $this->patch(route('notifications.read', [$customer, $stored->id]))->assertRedirect();
        $this->assertNotNull($stored->fresh()->read_at);
    }

    public function test_notification_uses_independent_queues(): void
    {
        $order = Order::factory()->create();
        $queues = (new OrderStatusChanged($order))->viaQueues();

        $this->assertSame('notifications-mail', $queues['mail']);
        $this->assertSame('notifications-ops', $queues['slack']);
        $this->assertSame('notifications-push', $queues[WebPushChannel::class]);
    }
}
