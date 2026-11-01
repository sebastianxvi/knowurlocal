<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgencyEvaluationModerationLog extends Model
{
    protected $fillable = ['agency_evaluation_id', 'admin_id', 'actor_label', 'action', 'reason', 'metadata'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function evaluation()
    {
        return $this->belongsTo(AgencyEvaluation::class, 'agency_evaluation_id')->withTrashed();
    }
}
