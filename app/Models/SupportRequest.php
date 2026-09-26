<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupportRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'agency_id',
        'question',
        'answer',
        'answer_image',
        'status',
        'ip_address',
        'answered_at',
        'answer_seen_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'answer_seen_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The citizen who submitted the support request.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The agency currently associated with the ticket.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * All official response attempts belonging to this ticket.
     */
    public function responses()
    {
        return $this->hasMany(SupportRequestResponse::class);
    }

    /**
     * The most recently created response attempt.
     */
    public function latestResponse()
    {
        return $this->hasOne(SupportRequestResponse::class)
            ->latestOfMany();
    }
}