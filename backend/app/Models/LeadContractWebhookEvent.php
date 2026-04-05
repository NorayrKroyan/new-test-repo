<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadContractWebhookEvent extends Model
{
    protected $fillable = [
        'lead_contract_request_id',
        'boldsign_document_id',
        'event_name',
        'signature_valid',
        'payload_json',
        'received_at',
        'processed_at',
        'process_error',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'signature_valid' => 'boolean',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function contractRequest()
    {
        return $this->belongsTo(LeadContractRequest::class, 'lead_contract_request_id');
    }
}
