<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadContractRequest extends Model
{
    protected $fillable = [
        'lead_id',
        'created_by_user_id',
        'source_type',
        'template_key',
        'template_id',
        'template_name',
        'uploaded_original_name',
        'uploaded_storage_path',
        'recipient_name',
        'recipient_email',
        'document_name',
        'subject',
        'message',
        'boldsign_document_id',
        'boldsign_document_url',
        'status',
        'boldsign_status',
        'prefill_payload_json',
        'boldsign_response_json',
        'last_error',
        'sent_at',
        'completed_at',
        'declined_at',
        'expired_at',
        'cancelled_at',
    ];

    protected $casts = [
        'prefill_payload_json' => 'array',
        'boldsign_response_json' => 'array',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'declined_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function webhookEvents()
    {
        return $this->hasMany(LeadContractWebhookEvent::class, 'lead_contract_request_id');
    }
}
