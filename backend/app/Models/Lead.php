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
        'merge_notes',
        'raw_payload',
        'linked_carrier_id',
        'duplicate_of_lead_id',
        'duplicate_basis',
        'assigned_admin_user_id',
        'merged_at',
        'merged_by_user_id',
        'sold_amount',
        'referral_fee',
        'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'source_created_at' => 'datetime',
            'sold_at' => 'datetime',
            'merged_at' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    public function carrier()
    {
        return $this->belongsTo(Carrier::class, 'linked_carrier_id');
    }

    public function duplicateMaster()
    {
        return $this->belongsTo(self::class, 'duplicate_of_lead_id');
    }

    public function duplicates()
    {
        return $this->hasMany(self::class, 'duplicate_of_lead_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_admin_user_id');
    }

    public function mergedBy()
    {
        return $this->belongsTo(User::class, 'merged_by_user_id');
    }

    public static function normalizePhone(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return $digits !== '' ? $digits : null;
    }

    public static function normalizeEmail(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value !== '' ? $value : null;
    }
}
