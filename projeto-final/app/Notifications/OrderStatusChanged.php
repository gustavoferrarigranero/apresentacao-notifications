<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

final class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->wants_email) {
            $channels[] = 'mail';
        }

        if ($notifiable->wants_push && $notifiable->pushSubscriptions()->exists()) {
            $channels[] = WebPushChannel::class;
        }

        if ($notifiable->is_operations) {
            $channels[] = 'slack';
        }

        return $channels;
    }

    public function viaQueues(): array
    {
        return [
            'database' => 'notifications-database',
            'mail' => 'notifications-mail',
            'slack' => 'notifications-ops',
            WebPushChannel::class => 'notifications-push',
        ];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'version' => 1,
            'order_id' => $this->order->id,
            'status' => $this->order->status->value,
            'message' => "O pedido #{$this->order->id} {$this->order->status->label()}.",
            'url' => route('orders.show', $this->order),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pedido a caminho')
            ->greeting("Olá, {$notifiable->name}")
            ->line("O pedido #{$this->order->id} saiu para entrega.")
            ->action('Acompanhar pedido', route('orders.show', $this->order))
            ->line('Você receberá uma atualização na entrega.');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text('Pedido saiu para entrega')
            ->headerBlock('Entrega em andamento')
            ->sectionBlock(function (SectionBlock $block): void {
                $block->text("Pedido #{$this->order->id}");
                $block->field("*Status:*\n{$this->order->status->label()}")->markdown();
                $block->field("*Cliente:*\n{$this->order->customer->name}")->markdown();
            });
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Pedido a caminho')
            ->body("O pedido #{$this->order->id} saiu para entrega.")
            ->action('Acompanhar', 'track_order')
            ->data([
                'url' => route('orders.show', $this->order),
                'order_id' => $this->order->id,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
