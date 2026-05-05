<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminInvite extends Model
{
    /**
     * 🔒 Mass assignable fields
     */
    protected $fillable = [
        'email',
        'token',
        'created_by',
        'expires_at',
        'used',
    ];

    /**
     * 🧠 Casts (important for security & correctness)
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * 🔗 Who created the invite (superadmin/admin)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 🔐 Check if invite is still valid
     */
    public function isValid()
    {
        return !$this->used && now()->lessThan($this->expires_at);
    }
}