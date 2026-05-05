<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AgencyType;

class Agency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_name',
        'agency_abbreviation',
        'agency_type_id', 
        'agency_location',
        'agency_description',
        'agency_landline',
        'agency_hotline',
        'agency_email',
        'agency_website',
        'agency_fb',
        'office_hours',
        'lat',
        'lng',
        'agency_image',
    ];

    
    public function faqs()
    {
        return $this->hasMany(Faq::class);
    }

    public function type()
    {
        return $this->belongsTo(AgencyType::class, 'agency_type_id');
    }

    
}