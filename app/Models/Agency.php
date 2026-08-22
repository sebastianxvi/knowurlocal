<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AgencyType;
use App\Models\Category;

class Agency extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_name',
        'agency_abbreviation',
        'agency_type_id', 
        'category_id',
        'agency_location',
        'agency_description',
        'services_offered',
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

    public function category()
{
    return $this->belongsTo(Category::class);
}

/**
 * All contact information belonging to this agency.
 */
public function contacts()
{
    return $this->hasMany(AgencyContact::class)
        ->orderBy('sort_order');
}
    
}