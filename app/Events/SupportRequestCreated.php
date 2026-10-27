<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportRequestCreated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public int $id,
        public string $question,
        public string $status,
        public ?int $agencyId,
        public ?string $agencyName,
        public string $userName,
        public string $createdAt,
    ) {
    }

    /**
     * The channel where the event will be broadcast.
     */
    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('admin.support-requests')];

        // Route new tickets to the assigned agency inbox as well as the
        // system-admin inbox. Unassigned tickets remain system-admin-only.
        if ($this->agencyId !== null) {
            $channels[] = new PrivateChannel('agency.' . (int) $this->agencyId . '.support-requests');
        }

        return $channels;
    }

    /**
     * The frontend event name.
     */
    public function broadcastAs(): string
    {
        return 'support.request.created';
    }

    /**
     * The exact data sent to the admin dashboard.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'status' => $this->status,
            'agency_id' => $this->agencyId,
            'agency_name' => $this->agencyName,
            'user_name' => $this->userName,
            'created_at' => $this->createdAt,
        ];
    }
}