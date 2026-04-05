<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadContractRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeadContractService
{
    public function __construct(
        protected BoldSignService $boldSign
    ) {
    }

    public function templateOptions(): array
    {
        return $this->boldSign->templateOptions();
    }

    public function historyForLead(Lead $lead)
    {
        return LeadContractRequest::query()
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->get();
    }

    public function sendForLead(Lead $lead, array $data, ?UploadedFile $file, ?int $userId = null): LeadContractRequest
    {
        if (!$this->boldSign->isConfigured()) {
            throw ValidationException::withMessages([
                'boldsign' => 'BoldSign integration is not configured.',
            ]);
        }

        $recipientEmail = $this->resolveRecipientEmail($lead, $data);
        if ($recipientEmail === '') {
            throw ValidationException::withMessages([
                'recipient_email' => 'Recipient email is required.',
            ]);
        }

        $recipientName = $this->resolveRecipientName($lead, $data);
        if ($recipientName === '') {
            throw ValidationException::withMessages([
                'recipient_name' => 'Recipient name is required.',
            ]);
        }

        $sourceType = (string) ($data['source_type'] ?? 'template');

        if ($sourceType === 'template' && blank($data['template_key'] ?? null)) {
            throw ValidationException::withMessages([
                'template_key' => 'Please select a contract template.',
            ]);
        }

        if ($sourceType === 'upload' && !$file) {
            throw ValidationException::withMessages([
                'file' => 'Please choose a contract file to upload.',
            ]);
        }

        $selectedTemplate = null;
        if ($sourceType === 'template') {
            $selectedTemplate = $this->boldSign->resolveTemplateSelection((string) $data['template_key']);
        }

        return DB::transaction(function () use ($lead, $data, $file, $userId, $recipientEmail, $recipientName, $sourceType, $selectedTemplate) {
            $request = LeadContractRequest::create([
                'lead_id' => $lead->id,
                'created_by_user_id' => $userId,
                'source_type' => $sourceType,
                'template_key' => $sourceType === 'template' ? ($data['template_key'] ?? null) : null,
                'template_id' => $sourceType === 'template' ? ($selectedTemplate['template_id'] ?? null) : null,
                'template_name' => $sourceType === 'template' ? ($selectedTemplate['template_name'] ?? null) : null,
                'uploaded_original_name' => $file?->getClientOriginalName(),
                'uploaded_storage_path' => null,
                'recipient_name' => $recipientName,
                'recipient_email' => $recipientEmail,
                'document_name' => trim((string) ($data['document_name'] ?? 'Lead Contract')),
                'subject' => trim((string) ($data['subject'] ?? '')),
                'message' => trim((string) ($data['message'] ?? '')),
                'status' => 'preparing',
                'prefill_payload_json' => [
                    'full_name' => $this->firstFilled([
                        $lead->full_name ?? null,
                        $lead->name ?? null,
                        trim(((string) ($lead->first_name ?? '')) . ' ' . ((string) ($lead->last_name ?? ''))),
                    ]),
                    'name' => $lead->name ?? null,
                    'first_name' => $lead->first_name ?? null,
                    'last_name' => $lead->last_name ?? null,
                    'email' => $recipientEmail,
                    'phone' => $lead->phone ?? null,
                    'city' => $lead->city ?? null,
                    'state' => $lead->state ?? null,
                    'usdot' => $lead->usdot ?? null,
                    'carrier_class' => $lead->carrier_class ?? null,
                    'truck_count' => $lead->truck_count ?? null,
                    'trailer_count' => $lead->trailer_count ?? null,
                    'insurance_answer' => $lead->insurance_answer ?? null,
                    'lead_date_choice' => $lead->lead_date_choice ?? null,
                ],
            ]);

            try {
                if ($sourceType === 'template') {
                    $response = $this->boldSign->sendFromTemplate([
                        'template_key' => $data['template_key'],
                        'template_id' => $selectedTemplate['template_id'] ?? null,
                        'recipient_name' => $recipientName,
                        'recipient_email' => $recipientEmail,
                        'document_name' => $request->document_name,
                        'subject' => $request->subject,
                        'message' => $request->message,
                        'lead_data' => $request->prefill_payload_json ?? [],
                    ]);
                } else {
                    $storedPath = $file->store('lead-contract-uploads', 'local');
                    $request->forceFill([
                        'uploaded_storage_path' => $storedPath,
                    ])->save();

                    $response = $this->boldSign->sendUploadedDocument([
                        'recipient_name' => $recipientName,
                        'recipient_email' => $recipientEmail,
                        'document_name' => $request->document_name,
                        'subject' => $request->subject,
                        'message' => $request->message,
                    ], $file);
                }

                $request->forceFill([
                    'boldsign_document_id' => $response['documentId'] ?? $response['document_id'] ?? null,
                    'boldsign_document_url' => $response['documentUrl'] ?? $response['document_url'] ?? null,
                    'boldsign_status' => $response['status'] ?? 'sent',
                    'status' => 'sent',
                    'boldsign_response_json' => $response,
                    'sent_at' => now(),
                ])->save();

                return $request->fresh();
            } catch (Throwable $e) {
                $request->forceFill([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ])->save();

                throw $e;
            }
        });
    }

    public function updateStatusByDocumentId(string $documentId, ?string $eventName, array $payload): ?LeadContractRequest
    {
        $request = LeadContractRequest::query()
            ->where('boldsign_document_id', $documentId)
            ->latest('id')
            ->first();

        if (!$request) {
            return null;
        }

        $eventKey = strtolower(trim((string) $eventName));

        $status = match ($eventKey) {
            'sent', 'documentsent' => 'sent',
            'viewed', 'documentviewed' => 'viewed',
            'completed', 'documentcompleted', 'signed' => 'completed',
            'declined', 'documentdeclined' => 'declined',
            'expired', 'documentexpired' => 'expired',
            'cancelled', 'revoked', 'documentrevoked' => 'cancelled',
            default => $request->status,
        };

        $request->forceFill([
            'status' => $status,
            'boldsign_status' => $eventName ?: $request->boldsign_status,
            'completed_at' => $status === 'completed' ? now() : $request->completed_at,
            'declined_at' => $status === 'declined' ? now() : $request->declined_at,
            'expired_at' => $status === 'expired' ? now() : $request->expired_at,
            'cancelled_at' => $status === 'cancelled' ? now() : $request->cancelled_at,
        ])->save();

        return $request->fresh();
    }

    protected function resolveRecipientEmail(Lead $lead, array $data): string
    {
        return trim((string) $this->firstFilled([
            $data['recipient_email'] ?? null,
            $lead->email ?? null,
            $lead->recipient_email ?? null,
            $lead->contact_email ?? null,
        ]));
    }

    protected function resolveRecipientName(Lead $lead, array $data): string
    {
        return trim((string) $this->firstFilled([
            $data['recipient_name'] ?? null,
            $lead->full_name ?? null,
            $lead->name ?? null,
            trim(((string) ($lead->first_name ?? '')) . ' ' . ((string) ($lead->last_name ?? ''))),
            $lead->contact_name ?? null,
            $lead->company_name ?? null,
        ]));
    }

    protected function firstFilled(array $values): ?string
    {
        foreach ($values as $value) {
            $value = is_string($value) ? trim($value) : $value;
            if ($value !== null && $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }
}
