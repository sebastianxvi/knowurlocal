<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}
