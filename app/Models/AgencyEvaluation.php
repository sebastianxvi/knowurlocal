<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AgencyEvaluation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'agency_id',
        'user_id',
        'rating',
        'comment',
        'status',
        'keep_identity_hidden',
    ];

    protected function casts(): array
    {
        return ['rating' => 'integer', 'keep_identity_hidden' => 'boolean'];
    }

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function response()
    {
        return $this->hasOne(AgencyEvaluationResponse::class, 'agency_evaluation_id');
    }

    public function reports()
    {
        return $this->hasMany(AgencyEvaluationReport::class, 'agency_evaluation_id');
    }

    public function helpfulVotes()
    {
        return $this->hasMany(AgencyEvaluationHelpfulVote::class, 'agency_evaluation_id');
    }
}
