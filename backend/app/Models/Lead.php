<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'source_name',
        'ad_name',
        'platform',
        'source_created_at',
        'lead_date_choice',
        'insurance_answer',
        'full_name',
        'email',
        'phone',
        'city',
        'state',
        'carrier_class',
        'usdot',
        'truck_count',
        'trailer_count',
        'lead_status',
        'notes',
        'raw_payload',
        'linked_carrier_id',
        'assigned_admin_user_id',
        'sold_amount',
        'referral_fee',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'source_created_at' => 'datetime',
            'sold_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'linked_carrier_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_user_id');
    }
}
