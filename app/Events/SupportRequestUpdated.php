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
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.support-requests'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'support.request.updated';
    }

    public function broadcastWith(): array
    {
        $supportRequest = SupportRequest::query()
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
        ];
    }
}
