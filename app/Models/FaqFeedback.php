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
        'faq_version_id',
        'user_id',
        'rating',
        'reason',
        'comment',
    ];

    public function chatbotLog(): BelongsTo
    {
        return $this->belongsTo(ChatbotLog::class);
    }

    public function faqVersion(): BelongsTo
    {
        return $this->belongsTo(FaqVersion::class, 'faq_version_id');
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
