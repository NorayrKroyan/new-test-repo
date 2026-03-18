<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadCallSession extends Model
{
    protected $fillable = [
        'lead_id',
        'qualification_script_id',
        'admin_user_id',
        'current_step_id',
        'recommended_stage_id',
        'status',
        'call_result',
        'recommended_status',
        'recommended_stage_order',
        'score',
        'qualifies_for_conversion',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'qualifies_for_conversion' => 'boolean',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function script(): BelongsTo
    {
        return $this->belongsTo(QualificationScript::class, 'qualification_script_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(QualificationScriptStep::class, 'current_step_id');
    }

    public function recommendedStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'recommended_stage_id');
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LeadCallAnswer::class)->orderBy('id');
    }
}
