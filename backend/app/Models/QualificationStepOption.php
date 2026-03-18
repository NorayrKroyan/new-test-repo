<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualificationStepOption extends Model
{
    protected $fillable = [
        'qualification_script_step_id',
        'next_step_id',
        'label',
        'value',
        'sort_order',
        'score_delta',
        'disqualifies_lead',
        'requires_note',
        'recommended_status',
        'recommended_stage_order',
    ];

    protected $casts = [
        'disqualifies_lead' => 'boolean',
        'requires_note' => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(QualificationScriptStep::class, 'qualification_script_step_id');
    }

    public function nextStep(): BelongsTo
    {
        return $this->belongsTo(QualificationScriptStep::class, 'next_step_id');
    }
}
