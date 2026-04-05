<?php

namespace App\Services;

use App\Models\JobAssignment;
use App\Models\JobBoldSignTemplate;
use App\Models\Lead;
use App\Models\LeadContractRequest;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeadContractService
{
    public function __construct(
        protected BoldSignService $boldSign,
    ) {
    }

    public function templateOptions(?Lead $lead = null, ?int $jobAvailableId = null): array
    {
        $templates = $this->boldSign->templateOptions();

        $resolvedJobAvailableId = $this->resolveJobAvailableId($lead, $jobAvailableId);
        if (!$resolvedJobAvailableId) {
            return $templates;
        }

        $allowedTemplateIds = JobBoldSignTemplate::query()
            ->where('job_available_id', $resolvedJobAvailableId)
            ->pluck('template_id')
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values()
            ->all();

        if (empty($allowedTemplateIds)) {
            return [];
        }

        return array_values(array_filter($templates, function (array $template) use ($allowedTemplateIds) {
            $templateId = trim((string) ($template['template_id'] ?? ''));
            return $templateId !== '' && in_array($templateId, $allowedTemplateIds, true);
        }));
    }

    public function historyForLead(Lead $lead, ?User $viewer = null): array
    {
        $canAccessSensitiveDocuments = $this->canAccessSensitiveDocuments($viewer);

        return LeadContractRequest::query()
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->get()
            ->map(fn (LeadContractRequest $row) => $this->presentHistoryRow($row, $canAccessSensitiveDocuments))
            ->values()
            ->all();
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
        $templateSelection = trim((string) ($data['template_id'] ?? $data['template_key'] ?? ''));
        $resolvedJobAvailableId = $this->resolveJobAvailableId(
            $lead,
            isset($data['job_available_id']) ? (int) $data['job_available_id'] : null
        );

        if ($sourceType === 'template' && $templateSelection === '') {
            throw ValidationException::withMessages([
                'template_key' => 'Please select a contract template.',
            ]);
        }

        if ($sourceType === 'upload' && !$file) {
            throw ValidationException::withMessages([
                'file' => 'Please choose a contract file to upload.',
            ]);
        }

        if ($sourceType === 'upload' && $resolvedJobAvailableId) {
            throw ValidationException::withMessages([
                'source_type' => 'Job-assigned leads must use one of the allowed BoldSign templates.',
            ]);
        }

        $selectedTemplate = null;
        if ($sourceType === 'template') {
            $selectedTemplate = $this->boldSign->resolveTemplateSelection($templateSelection);
            $this->assertTemplateAllowedForLead($selectedTemplate, $lead, $resolvedJobAvailableId);
        }

        return DB::transaction(function () use (
            $lead,
            $data,
            $file,
            $userId,
            $recipientEmail,
            $recipientName,
            $sourceType,
            $selectedTemplate,
            $templateSelection
        ) {
            $request = LeadContractRequest::create([
                'lead_id' => $lead->id,
                'created_by_user_id' => $userId,
                'source_type' => $sourceType,
                'template_key' => $sourceType === 'template' ? (($selectedTemplate['key'] ?? null) ?: $templateSelection) : null,
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
                        'template_key' => $selectedTemplate['key'] ?? $templateSelection,
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

    public function managementDocumentUrlForLead(Lead $lead, int $contractId, ?User $viewer = null): array
    {
        if (!$this->canAccessSensitiveDocuments($viewer)) {
            throw new AuthorizationException('Only management can access completed contract documents.');
        }

        $request = LeadContractRequest::query()
            ->where('lead_id', $lead->id)
            ->whereKey($contractId)
            ->firstOrFail();

        $documentUrl = trim((string) ($request->boldsign_document_url ?? ''));
        if ($documentUrl === '') {
            throw ValidationException::withMessages([
                'document' => 'No completed BoldSign document URL is available for this record.',
            ]);
        }

        return [
            'id' => $request->id,
            'lead_id' => $request->lead_id,
            'document_name' => $request->document_name,
            'status' => $request->status,
            'boldsign_document_url' => $documentUrl,
        ];
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

    protected function resolveJobAvailableId(?Lead $lead = null, ?int $jobAvailableId = null): ?int
    {
        if ($jobAvailableId && $jobAvailableId > 0) {
            return $jobAvailableId;
        }

        $leadJobAvailableId = (int) ($lead?->job_available_id ?? 0);
        if ($leadJobAvailableId > 0) {
            return $leadJobAvailableId;
        }

        if ($lead?->id) {
            $assignedJobId = (int) JobAssignment::query()
                ->where('lead_id', $lead->id)
                ->orderByDesc('id')
                ->value('job_available_id');

            if ($assignedJobId > 0) {
                return $assignedJobId;
            }
        }

        return null;
    }

    protected function assertTemplateAllowedForLead(array $selectedTemplate, Lead $lead, ?int $jobAvailableId = null): void
    {
        $resolvedJobAvailableId = $this->resolveJobAvailableId($lead, $jobAvailableId);
        if (!$resolvedJobAvailableId) {
            return;
        }

        $templateId = trim((string) ($selectedTemplate['template_id'] ?? ''));
        if ($templateId === '') {
            throw ValidationException::withMessages([
                'template_key' => 'Selected BoldSign template could not be resolved.',
            ]);
        }

        $allowed = JobBoldSignTemplate::query()
            ->where('job_available_id', $resolvedJobAvailableId)
            ->where('template_id', $templateId)
            ->exists();

        if (!$allowed) {
            throw ValidationException::withMessages([
                'template_key' => 'This template is not allowed for the selected job.',
            ]);
        }
    }

    protected function presentHistoryRow(LeadContractRequest $request, bool $includeSensitive): array
    {
        $row = $request->toArray();

        if (!$includeSensitive) {
            unset(
                $row['boldsign_document_url'],
                $row['boldsign_response_json'],
                $row['prefill_payload_json'],
                $row['uploaded_storage_path']
            );
        }

        $row['can_open_document'] = $includeSensitive && filled($request->boldsign_document_url);

        return $row;
    }

    protected function canAccessSensitiveDocuments(?User $viewer): bool
    {
        return strtolower(trim((string) ($viewer?->role ?? ''))) === 'super_admin';
    }
}
