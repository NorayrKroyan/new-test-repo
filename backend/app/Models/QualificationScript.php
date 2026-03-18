<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QualificationScript extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'applies_to_ad_name',
        'applies_to_platform',
        'is_default',
        'is_active',
        'priority',
        'version',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function steps(): HasMany
    {
        return $this->hasMany(QualificationScriptStep::class)->orderBy('sort_order')->orderBy('id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(LeadCallSession::class);
    }
}
