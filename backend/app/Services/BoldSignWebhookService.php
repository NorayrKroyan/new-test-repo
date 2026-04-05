<?php

namespace App\Services;

use App\Models\LeadContractWebhookEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class BoldSignWebhookService
{
    public function __construct(
        protected BoldSignService $boldSign,
        protected LeadContractService $contracts
    ) {
    }

    public function handle(string $rawBody, ?string $signatureHeader, array $payload): LeadContractWebhookEvent
    {
        $signatureValid = $this->boldSign->verifyWebhookSignature($signatureHeader, $rawBody);
        $documentId = (string) (
            Arr::get($payload, 'document.id') ??
            Arr::get($payload, 'documentId') ??
            Arr::get($payload, 'data.documentId') ??
            ''
        );

        $eventName = (string) (
            Arr::get($payload, 'event.eventType') ??
            Arr::get($payload, 'eventType') ??
            Arr::get($payload, 'event_name') ??
            ''
        );

        return DB::transaction(function () use ($payload, $signatureValid, $documentId, $eventName) {
            $contract = $documentId !== ''
                ? $this->contracts->updateStatusByDocumentId($documentId, $eventName, $payload)
                : null;

            return LeadContractWebhookEvent::create([
                'lead_contract_request_id' => $contract?->id,
                'boldsign_document_id' => $documentId ?: null,
                'event_name' => $eventName ?: null,
                'signature_valid' => $signatureValid,
                'payload_json' => $payload,
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        });
    }
}
