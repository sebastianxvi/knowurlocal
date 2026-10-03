<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Faq extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',

        // English source content.
        'question',
        'answer',

        // Filipino/Taglish translation.
        'question_fil',
        'answer_fil',

        'keywords',
        'image',
        'response_components'
    ];

    protected $casts = [
        'response_components' => 'array',
    ];

    public function agency()
{
    return $this->belongsTo(Agency::class);
}

public function feedback(): HasMany
{
    return $this->hasMany(FaqFeedback::class);
}

public function versions(): HasMany
{
    return $this->hasMany(FaqVersion::class)->orderByDesc('version_number');
}

public function currentVersion(): BelongsTo
{
    return $this->belongsTo(FaqVersion::class, 'current_version_id');
}

/**
 * Feedback belonging only to the response version currently published.
 */
public function currentFeedback(): HasMany
{
    return $this->hasMany(FaqFeedback::class, 'faq_version_id', 'current_version_id');
}
}
