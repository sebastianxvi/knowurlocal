<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqVersion extends Model
{
    protected $fillable = [
        'faq_id',
        'version_number',
        'agency_id',
        'question',
        'answer',
        'question_fil',
        'answer_fil',
        'keywords',
        'image',
        'response_components',
        'changed_by',
        'superseded_at',
    ];

    protected $casts = [
        'response_components' => 'array',
        'superseded_at' => 'datetime',
    ];

    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class)->withTrashed();
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class)->withTrashed();
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(FaqFeedback::class);
    }
}
