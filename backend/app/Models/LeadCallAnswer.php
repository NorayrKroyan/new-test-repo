<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadCallAnswer extends Model
{
    protected $fillable = [
        'lead_call_session_id',
        'qualification_script_step_id',
        'triggered_stage_id',
        'step_key_snapshot',
        'prompt_snapshot',
        'answer_value',
        'answer_label',
        'answer_text',
        'score_delta',
        'is_disqualifying',
        'triggered_status',
        'triggered_stage_order',
        'answered_at',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_disqualifying' => 'boolean',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(LeadCallSession::class, 'lead_call_session_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(QualificationScriptStep::class, 'qualification_script_step_id');
    }

    public function triggeredStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'triggered_stage_id');
    }
}
