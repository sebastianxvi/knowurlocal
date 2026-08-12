<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;
    
    protected $fillable = [

        'category_name',
        'display_color',

    ];

    /**
     * Agencies belonging to this category.
     */
    public function agencies()
    {
        return $this->hasMany(Agency::class);
    }
}