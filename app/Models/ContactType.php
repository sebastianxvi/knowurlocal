<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactType extends Model
{
    /**
     * Fields that may be safely mass assigned.
     */
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
    ];

    /**
     * Cast database values into their appropriate PHP types.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Contacts using this predefined contact type.
     */
    public function agencyContacts()
    {
        return $this->hasMany(
            AgencyContact::class
        );
    }
}