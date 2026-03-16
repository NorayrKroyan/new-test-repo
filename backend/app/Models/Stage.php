<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $fillable = [
        'stage_name',
        'stage_group',
        'stage_order',
    ];

    protected $appends = [
        'is_funnel_stage',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_stage_id');
    }

    public function getIsFunnelStageAttribute(): bool
    {
        return (int) $this->stage_order > 0 && (int) $this->stage_order < 10;
    }
}
