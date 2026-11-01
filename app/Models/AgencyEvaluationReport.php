<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyEvaluationReport extends Model
{
    protected $fillable = [
        'agency_evaluation_id', 'reporter_id', 'reason', 'details',
        'status', 'reviewed_by', 'reviewed_at', 'resolution_note',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function evaluation()
    {
        return $this->belongsTo(AgencyEvaluation::class, 'agency_evaluation_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
