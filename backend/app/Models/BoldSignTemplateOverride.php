<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoldSignTemplateOverride extends Model
{
    protected $table = 'boldsign_template_overrides';

    protected $guarded = [];

    protected $casts = [
        'field_map_json' => 'array',
        'is_enabled' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BoldSignTemplate::class, 'template_id', 'template_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
