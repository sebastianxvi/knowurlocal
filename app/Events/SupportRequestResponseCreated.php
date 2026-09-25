<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportRequestResponseCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $userId,
        public int $supportRequestId,
        public int $responseId,
        public string $status,
    ) {
    }

    /**
     * The private channel belonging to the citizen
     * who owns the support request.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'App.Models.User.' . $this->userId
            ),
        ];
    }

    /**
     * The frontend event name.
     */
    public function broadcastAs(): string
    {
        return 'support.request.response.created';
    }

    /**
     * Send only the minimum information needed
     * by the citizen-side realtime interface.
     */
    public function broadcastWith(): array
    {
        return [
            'support_request_id' => $this->supportRequestId,
            'response_id' => $this->responseId,
            'status' => $this->status,
        ];
    }
}