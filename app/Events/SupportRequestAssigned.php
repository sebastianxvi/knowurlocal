<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportRequestAssigned implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $id,
        public ?int $adminId,
        public ?string $adminName,
        public string $assignedAt,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin.support-requests')];
    }

    public function broadcastAs(): string
    {
        return 'support.request.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->id,
            'admin_id' => $this->adminId,
            'admin_name' => $this->adminName,
            'assigned_at' => $this->assignedAt,
        ];
    }
}
