<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BoldSignTemplate extends Model
{
    protected $table = 'boldsign_templates';

    protected $guarded = [];

    protected $casts = [
        'roles_json' => 'array',
        'shared_with_teams_json' => 'array',
        'raw_payload_json' => 'array',
        'is_active' => 'boolean',
        'last_modified_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function override(): HasOne
    {
        return $this->hasOne(BoldSignTemplateOverride::class, 'template_id', 'template_id');
    }

    public function jobAssignments(): HasMany
    {
        return $this->hasMany(JobBoldSignTemplate::class, 'template_id', 'template_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
