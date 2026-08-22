<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyContact extends Model
{
    /**
     * Fields that may be safely mass assigned.
     */
    protected $fillable = [
        'agency_id',
        'contact_type_id',
        'label',
        'value',
        'is_primary',
        'sort_order',
    ];


    /**
     * Cast database values into their intended PHP types.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];


    /**
     * The agency that owns this contact.
     */
    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }


    /**
     * The predefined contact type.
     */
    public function contactType()
    {
        return $this->belongsTo(ContactType::class);
    }
}