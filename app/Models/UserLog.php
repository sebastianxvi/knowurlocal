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
    'faq_id',
    'category_id',
    
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
    /*
     * Include soft-deleted agencies when loading an audit log.
     *
     * This is necessary because audit records must remain
     * historically readable even after an agency is deleted.
     */
    return $this->belongsTo(
        \App\Models\Agency::class
    )->withTrashed();
}

/**
 * 🔗 RELATION: Category
 *
 * Include soft-deleted categories so historical audit
 * records remain understandable after deletion.
 */
public function category()
{
    return $this->belongsTo(
        \App\Models\Category::class
    )->withTrashed();
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
 *
 * Converts internal page identifiers into
 * human-readable labels for the audit log.
 */
public function getPageLabelAttribute()
{
    return match ($this->page) {

        /*
         * Main NGA & NGO data management page.
         */
        'nga_ngo_management' =>
            'NGA & NGO Management',

        /*
         * Trashed-data recovery page.
         */
        'nga_ngo_recovery' =>
            'NGA & NGO Recovery',

        /*
         * Fallback for other page identifiers.
         *
         * This keeps existing pages working without
         * requiring every page to be manually added here.
         */
        default =>
            ucwords(
                str_replace(
                    '_',
                    ' ',
                    $this->page
                )
            ),
    };
}

    public function targetUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'target_user_id');
    }
}