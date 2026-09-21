<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportRequestResponse extends Model
{
    protected $fillable = [
        'support_request_id',
        'admin_id',
        'status',
        'forwarded_at',
        'responded_at',
        'follow_up_reason',
    ];

    protected $casts = [
        'forwarded_at' => 'datetime',
        'responded_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The support ticket this response belongs to.
     */
    public function supportRequest()
    {
        return $this->belongsTo(SupportRequest::class);
    }

    /**
     * The administrator who prepared this response.
     *
     * The relationship points to the users table because
     * administrators are represented by User records.
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * The structured components contained in this response.
     */
    public function components()
    {
        return $this->hasMany(SupportResponseComponent::class)
            ->orderBy('sort_order');
    }
}