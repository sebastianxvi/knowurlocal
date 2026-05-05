<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserLog extends Model
{
    /**
     * 🔒 Mass assignable fields
     */
    protected $fillable = [
    'user_id',
    'target_user_id',
    'agency_id',
    'action',
    'page',
    'role',
    'ip_address',
    'device',

    // 🔥 ADD THESE
    'old_values',
    'new_values',

    // existing
    'old_value',
    'new_value',
    'description',
];

protected $casts = [
    'old_values' => 'array',
    'new_values' => 'array',
];

    /**
     * 🔗 RELATION: Agency
     */
    public function agency()
    {
        return $this->belongsTo(\App\Models\Agency::class);
    }

    /**
     * 🔗 RELATION: User (ALL roles: user, admin, superadmin)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * 🎯 ACCESSOR: Actor Name (UNIFIED)
     */
    public function getActorNameAttribute()
    {
        // 🔐 Always trust users table (single source of truth)
        if ($this->user) {
            return $this->user->first_name . ' ' . $this->user->last_name;
        }

        return 'Guest';
    }

    /**
     * 🎯 ACCESSOR: Clean Action Label (UI friendly)
     */
    public function getActionLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * 🎯 ACCESSOR: Clean Page Label
     */
    public function getPageLabelAttribute()
    {
        return ucwords(str_replace('_', ' ', $this->page));
    }

    public function targetUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'target_user_id');
    }
}