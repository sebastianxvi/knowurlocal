<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupportResponseComponent extends Model
{
    protected $fillable = [
        'support_request_response_id',
        'type',
        'content',
        'label',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * The official response this component belongs to.
     */
    public function response()
    {
        return $this->belongsTo(
            SupportRequestResponse::class,
            'support_request_response_id'
        );
    }
}