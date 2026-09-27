<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CollaborationTask extends Model
{
    protected $fillable = [
        'created_by_id',
        'assigned_to_id',
        'task_type',
        'status',
        'target_type',
        'target_id',
        'target_label_snapshot',
        'title',
        'description',
        'due_at',
        'completed_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'target_type', 'target_id');
    }

    public function getTaskTypeLabelAttribute(): string
    {
        return match ($this->task_type) {
            'handoff' => 'Handoff',
            'review' => 'Review',
            'assist' => 'Assist',
            default => ucwords(str_replace('_', ' ', $this->task_type)),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'open' => 'Open',
            'in_progress' => 'In progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucwords(str_replace('_', ' ', $this->status)),
        };
    }

    public function getTargetModuleLabelAttribute(): ?string
    {
        return match ($this->target_type) {
            Agency::class => 'Agency',
            Faq::class => 'FAQ',
            Category::class => 'Category',
            SupportRequest::class => 'Support request',
            User::class => 'Account',
            default => null,
        };
    }

    public function getTargetLabelAttribute(): string
    {
        if ($this->target) {
            return match (true) {
                $this->target instanceof Agency => (string) $this->target->agency_name,
                $this->target instanceof Faq => (string) ($this->target->question ?: 'FAQ #' . $this->target->id),
                $this->target instanceof Category => (string) $this->target->category_name,
                $this->target instanceof SupportRequest => '#' . $this->target->id . ' — ' . (string) $this->target->question,
                $this->target instanceof User => trim($this->target->first_name . ' ' . $this->target->last_name) ?: $this->target->email,
                default => $this->target_label_snapshot ?: 'Linked record',
            };
        }

        return $this->target_label_snapshot ?: 'General task';
    }
}
