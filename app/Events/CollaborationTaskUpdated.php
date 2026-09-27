<?php

namespace App\Events;

use App\Models\CollaborationTask;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CollaborationTaskUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public CollaborationTask $task,
        public string $action,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.' . $this->task->created_by_id),
            new PrivateChannel('admin.' . $this->task->assigned_to_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'collaboration.task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'task' => [
                'id' => $this->task->id,
                'created_by_id' => $this->task->created_by_id,
                'assigned_to_id' => $this->task->assigned_to_id,
                'task_type' => $this->task->task_type,
                'status' => $this->task->status,
                'title' => $this->task->title,
                'target_type' => $this->task->target_type,
                'target_id' => $this->task->target_id,
                'target_label_snapshot' => $this->task->target_label_snapshot,
                'due_at' => optional($this->task->due_at)->toIso8601String(),
                'completed_at' => optional($this->task->completed_at)->toIso8601String(),
            ],
        ];
    }
}
