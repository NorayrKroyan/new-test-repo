<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobBoldSignTemplate extends Model
{
    protected $table = 'job_boldsign_templates';

    protected $guarded = [];

    public function template(): BelongsTo
    {
        return $this->belongsTo(BoldSignTemplate::class, 'template_id', 'template_id');
    }
}
