<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'image'
    ];

    public function agency()
{
    return $this->belongsTo(Agency::class);
}
}
