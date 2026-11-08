<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgencyProfileUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $agencyId,
        public int $profileRevision,
        public array $changedFields,
        public int $updatedBy,
        public string $reason,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('agency.' . $this->agencyId . '.profile')];
    }

    public function broadcastAs(): string
    {
        return 'agency.profile.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'agency_id' => $this->agencyId,
            'profile_revision' => $this->profileRevision,
            'changed_fields' => $this->changedFields,
            'updated_by' => $this->updatedBy,
            'reason' => $this->reason,
        ];
    }
}
