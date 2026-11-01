<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyEvaluationHelpfulVote extends Model
{
    protected $table = 'agency_evaluation_helpful_votes';

    protected $fillable = [
        'agency_evaluation_id',
        'user_id',
    ];

    public function evaluation()
    {
        return $this->belongsTo(AgencyEvaluation::class, 'agency_evaluation_id');
    }
}
