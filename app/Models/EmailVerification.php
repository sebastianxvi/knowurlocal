<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'first_name',
        'last_name',
        'password',
        'otp',
        'expires_at',
        'role',
        'invite_token',
    ];
}
