<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualificationScriptStep extends Model
{
    protected $fillable = [
        'qualification_script_id',
        'step_key',
        'title',
        'prompt_text',
        'help_text',
        'step_type',
        'sort_order',
        'is_required',
        'is_terminal',
        'disqualifies_lead',
        'recommended_status',
        'recommended_stage_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_terminal' => 'boolean',
        'disqualifies_lead' => 'boolean',
    ];

    public function script(): BelongsTo
    {
        return $this->belongsTo(QualificationScript::class, 'qualification_script_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QualificationStepOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(LeadCallAnswer::class);
    }
}
