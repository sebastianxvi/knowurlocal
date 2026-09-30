<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaqFeedback extends Model
{
    protected $table = 'faq_feedback';

    protected $fillable = [
        'chatbot_log_id',
        'faq_id',
        'user_id',
        'rating',
        'reason',
        'comment',
    ];

    public function chatbotLog(): BelongsTo
    {
        return $this->belongsTo(ChatbotLog::class);
    }

    public function faq(): BelongsTo
    {
        return $this->belongsTo(Faq::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
