<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotLog extends Model
{
    protected $fillable = [
        'user_id',
        'question',
        'answer',
        'agency_id',
        'type',
        'score',
        'ip_address',
    ];

    /**
     * 🔗 RELATION: CHATBOT LOG → AGENCY
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class, 'agency_id');
    }

    /**
     * 🔗 RELATION: CHATBOT LOG → USER
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}