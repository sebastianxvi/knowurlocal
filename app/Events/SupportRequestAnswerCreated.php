<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcasts a legacy/simple Support Request answer to the citizen.
 *
 * The response-builder workflow has its own response-created event.
 * This event keeps the older direct-answer workflow realtime as well.
 */
class SupportRequestAnswerCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $userId,
        public int $supportRequestId,
        public string $status = 'answered',
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'App.Models.User.' . $this->userId
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.request.answer.created';
    }

    public function broadcastWith(): array
    {
        return [
            'support_request_id' => $this->supportRequestId,
            'status' => $this->status,
        ];
    }
}
