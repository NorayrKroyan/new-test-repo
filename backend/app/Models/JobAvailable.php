<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobAvailable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'job_number',
        'title',
        'description',
        'origin_city',
        'origin_state',
        'destination_city',
        'destination_state',
        'equipment_type',
        'trailer_type',
        'weight',
        'rate',
        'rate_description',
        'status',
        'job_start_date',
        'primary_required',
        'spare_allowed',
        'customer_name',
        'customer_company',
        'customer_id',
        'created_by_admin_id',
        'posted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
            'expires_at' => 'datetime',
            'job_start_date' => 'date',
            'primary_required' => 'integer',
            'spare_allowed' => 'integer',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignments()
    {
        return $this->hasMany(JobAssignment::class, 'job_available_id');
    }
}
