<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_available_id',
        'carrier_id',
        'lead_id',
        'internal_poc_user_id',
        'source_type',
        'slot_type',
        'driver_name',
        'truck_number',
        'trailer_owner_type',
        'trailer_id',
        'expected_start_date',
        'readiness_checked_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expected_start_date' => 'date',
            'readiness_checked_at' => 'datetime',
        ];
    }

    public function job()
    {
        return $this->belongsTo(JobAvailable::class, 'job_available_id');
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'carrier_id');
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    public function internalPoc()
    {
        return $this->belongsTo(User::class, 'internal_poc_user_id');
    }
}
