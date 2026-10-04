<?php

namespace App\Events;

use App\Models\SupportRequest;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportRequestUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $id,
        public string $action = 'updated',
        public ?int $responseId = null,
        public ?int $userId = null,
    ) {
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('admin.support-requests'),
        ];

        /*
         * Citizen-side status changes use the same authoritative
         * event as the admin workspace. The user channel is private,
         * so only the owner of this inquiry receives the update.
         */
        if ($this->userId !== null) {
            $channels[] = new PrivateChannel(
                'App.Models.User.' . $this->userId
            );
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'support.request.updated';
    }

    public function broadcastWith(): array
    {
        $supportRequest = SupportRequest::withTrashed()
            ->with(['user', 'agency'])
            ->find($this->id);

        if (!$supportRequest) {
            return [
                'id' => $this->id,
                'action' => $this->action,
                'deleted' => true,
            ];
        }

        return [
            'id' => (int) $supportRequest->id,
            'action' => $this->action,
            'response_id' => $this->responseId,
            'status' => (string) $supportRequest->status,
            'question' => (string) $supportRequest->question,
            'agency_id' => $supportRequest->agency_id !== null
                ? (int) $supportRequest->agency_id
                : null,
            'agency_name' => $supportRequest->agency?->agency_name,
            'user_name' => $supportRequest->user?->first_name ?? 'Guest',
            'created_at' => $supportRequest->created_at?->toIso8601String(),
            'updated_at' => $supportRequest->updated_at?->toIso8601String(),
            'answer' => $supportRequest->answer,
            'answer_image' => $supportRequest->answer_image,
            'deleted_at' => $supportRequest->deleted_at?->toIso8601String(),
            'trash_reason' => $supportRequest->trash_reason,
        ];
    }
}
