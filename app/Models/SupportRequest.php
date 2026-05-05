<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequest extends Model
{
    protected $fillable = [
        'user_id',
        'agency_id',
        'question',
        'answer',
        'status',
        'ip_address',
    ];

    /**
     * 🔗 RELATION: SUPPORT REQUEST → USER
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 🔗 RELATION: SUPPORT REQUEST → AGENCY
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }
}