<?php

namespace App\Events;

use App\Models\UserNotification;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public UserNotification $notification)
    {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('App.Models.User.' . (int) $this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'user.notification.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (int) $this->notification->id,
            'type' => (string) $this->notification->type,
            'title' => (string) $this->notification->title,
            'message' => (string) $this->notification->message,
            'support_request_id' => $this->notification->support_request_id !== null
                ? (int) $this->notification->support_request_id
                : null,
            'created_at' => $this->notification->created_at?->toIso8601String(),
            'open_url' => route('user.notifications.open', $this->notification->id),
        ];
    }
}
