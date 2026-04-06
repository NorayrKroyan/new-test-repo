<?php

namespace App\Services;

use App\Models\LeadContractWebhookEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class BoldSignWebhookService
{
    public function __construct(
        protected BoldSignService $boldSign,
        protected LeadContractService $contracts
    ) {
    }

    public function handle(string $rawBody, ?string $signatureHeader, array $payload): LeadContractWebhookEvent
    {
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

        $signatureValid = $this->boldSign->verifyWebhookSignature($signatureHeader, $rawBody);

        if ($this->isVerificationEvent($eventName)) {
            return $this->createWebhookEvent(
                leadContractRequestId: null,
                documentId: $documentId,
                eventName: $eventName,
                signatureValid: $signatureValid,
                payload: $payload,
                processed: true,
            );
        }

        if (!$signatureValid) {
            $this->createWebhookEvent(
                leadContractRequestId: null,
                documentId: $documentId,
                eventName: $eventName,
                signatureValid: false,
                payload: $payload,
                processed: false,
            );

            throw new AccessDeniedHttpException('Invalid BoldSign webhook signature.');
        }

        return DB::transaction(function () use ($payload, $signatureValid, $documentId, $eventName) {
            $contract = $documentId !== ''
                ? $this->contracts->updateStatusByDocumentId($documentId, $eventName, $payload)
                : null;

            return $this->createWebhookEvent(
                leadContractRequestId: $contract?->id,
                documentId: $documentId,
                eventName: $eventName,
                signatureValid: $signatureValid,
                payload: $payload,
                processed: true,
            );
        });
    }

    protected function isVerificationEvent(string $eventName): bool
    {
        return mb_strtolower(trim($eventName)) === 'verification';
    }

    protected function createWebhookEvent(
        ?int $leadContractRequestId,
        string $documentId,
        string $eventName,
        bool $signatureValid,
        array $payload,
        bool $processed
    ): LeadContractWebhookEvent {
        return LeadContractWebhookEvent::create([
            'lead_contract_request_id' => $leadContractRequestId,
            'boldsign_document_id' => $documentId !== '' ? $documentId : null,
            'event_name' => $eventName !== '' ? $eventName : null,
            'signature_valid' => $signatureValid,
            'payload_json' => $payload,
            'received_at' => now(),
            'processed_at' => $processed ? now() : null,
        ]);
    }
}
