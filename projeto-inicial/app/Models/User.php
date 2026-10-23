<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\HasPushSubscriptions;

final class User extends Authenticatable
{
    use HasFactory, HasPushSubscriptions, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'wants_email',
        'wants_push',
        'is_operations',
        'slack_channel',
    ];

    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wants_email' => 'boolean',
            'wants_push' => 'boolean',
            'is_operations' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function routeNotificationForSlack(Notification $notification): string
    {
        return $this->slack_channel ?: (string) config('services.slack.notifications.channel');
    }
}
