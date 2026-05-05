<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyType extends Model
{
    protected $fillable = ['name'];

    public function agencies()
    {
        return $this->hasMany(Agency::class);
    }
}