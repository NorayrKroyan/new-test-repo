<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadMapPoint extends Model
{
    protected $fillable = [
        'lead_id',
        'query_source',
        'geocode_query',
        'resolved_city',
        'resolved_state',
        'resolved_postal_code',
        'formatted_address',
        'place_id',
        'lat',
        'lng',
        'geocode_status',
        'geocoded_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'geocoded_at' => 'datetime',
        ];
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
