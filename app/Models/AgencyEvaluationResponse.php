<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyEvaluationResponse extends Model
{
    protected $fillable = ['agency_evaluation_id', 'responder_id', 'responder_role', 'body'];

    public function evaluation()
    {
        return $this->belongsTo(AgencyEvaluation::class, 'agency_evaluation_id');
    }

    public function responder()
    {
        return $this->belongsTo(User::class, 'responder_id');
    }
}
