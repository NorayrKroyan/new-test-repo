<?php

namespace App\Services;

use App\Models\BoldSignTemplate;
use App\Models\BoldSignTemplateOverride;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BoldSignService
{
    public function isConfigured(): bool
    {
        return filled(config('services.boldsign.api_key') ?: env('BOLDSIGN_API_KEY'));
    }

    public function templateOptions(): array
    {
        $databaseOptions = $this->databaseTemplateOptions();
        if (!empty($databaseOptions)) {
            return $databaseOptions;
        }

        return $this->fallbackTemplateOptions();
    }

    public function resolveTemplateSelection(string $selection): array
    {
        $selection = trim($selection);
        if ($selection === '') {
            throw new RuntimeException('BoldSign template selection is required.');
        }

        $databaseTemplate = BoldSignTemplate::query()
            ->with('override')
            ->where('template_id', $selection)
            ->first();

        if ($databaseTemplate) {
            if (!$this->isDatabaseTemplateEnabled($databaseTemplate)) {
                throw new RuntimeException('The selected BoldSign template is disabled or inactive.');
            }

            return [
                'key' => $databaseTemplate->template_id,
                'template_id' => $databaseTemplate->template_id,
                'template_name' => $databaseTemplate->template_name ?: $databaseTemplate->template_id,
                'config' => $this->databaseTemplateConfig($databaseTemplate),
            ];
        }

        $localTemplates = $this->localTemplateDefinitions();

        if (isset($localTemplates[$selection])) {
            $local = $localTemplates[$selection];

            return [
                'key' => $selection,
                'template_id' => $local['template_id'] ?? $selection,
                'template_name' => $local['label'] ?? $selection,
                'config' => $local,
            ];
        }

        foreach ($this->fallbackTemplateOptions() as $option) {
            if (
                (string) ($option['key'] ?? '') === $selection ||
                (string) ($option['template_id'] ?? '') === $selection
            ) {
                $local = null;
                foreach ($localTemplates as $key => $template) {
                    if ((string) ($template['template_id'] ?? '') === (string) ($option['template_id'] ?? '')) {
                        $local = $template;
                        $selection = $key;
                        break;
                    }
                }

                return [
                    'key' => $selection,
                    'template_id' => $option['template_id'] ?? $selection,
                    'template_name' => $option['template_name'] ?? ($option['label'] ?? $selection),
                    'config' => $local,
                ];
            }
        }

        return [
            'key' => $selection,
            'template_id' => $selection,
            'template_name' => $selection,
            'config' => null,
        ];
    }

    public function sendFromTemplate(array $payload): array
    {
        $resolved = $this->resolveTemplateSelection((string) ($payload['template_key'] ?? $payload['template_id'] ?? ''));
        $templateId = (string) ($resolved['template_id'] ?? '');

        if ($templateId === '') {
            throw new RuntimeException('BoldSign template ID could not be resolved.');
        }

        $templateProperties = $this->getTemplateProperties($templateId);
        $selectedRole = $this->resolveTemplateRole($templateProperties, $resolved['config'] ?? null);

        $allRoles = collect((array) ($templateProperties['roles'] ?? []));
        if ($allRoles->isEmpty()) {
            throw new RuntimeException('BoldSign template has no roles.');
        }

        $selectedOriginalIndex = (int) ($selectedRole['index'] ?? $selectedRole['roleIndex'] ?? 0);
        if ($selectedOriginalIndex <= 0) {
            throw new RuntimeException('BoldSign template role index could not be resolved.');
        }

        $roleRemovalIndices = $allRoles
            ->map(fn (array $role) => (int) ($role['index'] ?? $role['roleIndex'] ?? 0))
            ->filter(fn (int $index) => $index > 0 && $index !== $selectedOriginalIndex)
            ->values()
            ->all();

        $removedBeforeSelected = collect($roleRemovalIndices)
            ->filter(fn (int $index) => $index < $selectedOriginalIndex)
            ->count();

        $renumberedRoleIndex = $selectedOriginalIndex - $removedBeforeSelected;

        $requestRole = [
            'roleIndex' => $renumberedRoleIndex,
            'signerName' => (string) ($payload['recipient_name'] ?? ''),
            'signerEmail' => (string) ($payload['recipient_email'] ?? ''),
            'signerType' => (string) ($selectedRole['signerType'] ?? 'Signer'),
            'deliveryMode' => 'Email',
            'locale' => 'EN',
        ];

        if (filled($selectedRole['name'] ?? null)) {
            $requestRole['signerRole'] = (string) $selectedRole['name'];
        }

        $existingFormFields = $this->buildExistingFormFields(
            (array) ($payload['lead_data'] ?? []),
            $resolved['config'] ?? null
        );

        if (!empty($existingFormFields)) {
            $requestRole['existingFormFields'] = $existingFormFields;
        }

        $body = [
            'title' => (string) ($payload['document_name'] ?? ($resolved['template_name'] ?? 'Lead Contract')),
            'message' => (string) ($payload['message'] ?? ''),
            'roles' => [$requestRole],
            'disableEmails' => false,
        ];

        if (!empty($roleRemovalIndices)) {
            $body['roleRemovalIndices'] = $roleRemovalIndices;
        }

        return $this->request()
            ->post('/v1/template/send?templateId=' . urlencode($templateId), $body)
            ->throw()
            ->json();
    }

    public function sendUploadedDocument(array $payload, UploadedFile $file): array
    {
        $recipientName = trim((string) ($payload['recipient_name'] ?? ''));
        $recipientEmail = trim((string) ($payload['recipient_email'] ?? ''));

        if ($recipientName === '') {
            throw new RuntimeException('Recipient name is required.');
        }

        if ($recipientEmail === '') {
            throw new RuntimeException('Recipient email is required.');
        }

        $signer = [
            'Name' => $recipientName,
            'EmailAddress' => $recipientEmail,
            'SignerType' => 'Signer',
            'Locale' => 'EN',
        ];

        return $this->multipartRequest()
            ->attach('Files', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
            ->post('/v1/document/send', [
                'Title' => $payload['document_name'] ?? $file->getClientOriginalName(),
                'Subject' => $payload['subject'] ?? '',
                'Message' => $payload['message'] ?? '',
                'Signers' => json_encode($signer, JSON_UNESCAPED_SLASHES),
                'AutoDetectFields' => 'true',
                'UseTextTags' => 'false',
                'DisableEmails' => 'false',
            ])
            ->throw()
            ->json();
    }

    public function verifyWebhookSignature(?string $signatureHeader, string $rawBody): bool
    {
        $secret = (string) env('BOLDSIGN_WEBHOOK_SECRET', '');
        if ($secret === '' || !$signatureHeader) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $secret);

        return hash_equals($expected, trim($signatureHeader));
    }

    protected function request(): PendingRequest
    {
        $baseUrl = rtrim((string) (config('services.boldsign.base_url') ?: env('BOLDSIGN_BASE_URL', 'https://api.boldsign.com')), '/');
        $apiKey = (string) (config('services.boldsign.api_key') ?: env('BOLDSIGN_API_KEY'));

        return Http::baseUrl($baseUrl)
            ->timeout(60)
            ->acceptJson()
            ->withHeaders([
                'X-API-KEY' => $apiKey,
            ]);
    }

    protected function multipartRequest(): PendingRequest
    {
        return $this->request()->asMultipart();
    }

    protected function localTemplateDefinitions(): array
    {
        return (array) config('boldsign_templates.templates', []);
    }

    protected function databaseTemplateOptions(): array
    {
        $rows = BoldSignTemplate::query()
            ->with('override')
            ->orderByRaw('CASE WHEN is_active = 1 THEN 0 ELSE 1 END')
            ->orderBy('template_name')
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        return $rows
            ->filter(fn (BoldSignTemplate $template) => $this->isDatabaseTemplateEnabled($template))
            ->map(function (BoldSignTemplate $template) {
                return [
                    'key' => $template->template_id,
                    'label' => $template->template_name ?: $template->template_id,
                    'template_id' => $template->template_id,
                    'template_name' => $template->template_name ?: $template->template_id,
                    'has_mapping' => true,
                    'source' => 'db',
                ];
            })
            ->values()
            ->all();
    }

    protected function isDatabaseTemplateEnabled(BoldSignTemplate $template): bool
    {
        if (!$template->is_active) {
            return false;
        }

        $override = $template->override;
        if (!$override) {
            return true;
        }

        return (bool) $override->is_enabled;
    }

    protected function databaseTemplateConfig(BoldSignTemplate $template): array
    {
        /** @var BoldSignTemplateOverride|null $override */
        $override = $template->override;

        return [
            'signer_role' => filled($override?->preferred_signer_role)
                ? trim((string) $override->preferred_signer_role)
                : null,
            'signer_role_index' => $override?->preferred_signer_role_index
                ? (int) $override->preferred_signer_role_index
                : null,
            'field_map' => (array) ($override?->field_map_json ?? []),
        ];
    }

    protected function fallbackTemplateOptions(): array
    {
        $localTemplates = $this->localTemplateDefinitions();

        try {
            $remoteTemplates = $this->listRemoteTemplates();
        } catch (Throwable $e) {
            if (!empty($localTemplates)) {
                return collect($localTemplates)->map(function (array $template, string $key) {
                    return [
                        'key' => $key,
                        'label' => $template['label'] ?? $key,
                        'template_id' => $template['template_id'] ?? null,
                        'template_name' => $template['label'] ?? $key,
                        'has_mapping' => true,
                        'source' => 'config',
                    ];
                })->values()->all();
            }

            throw new RuntimeException('BoldSign template list failed: ' . $e->getMessage(), previous: $e);
        }

        if (empty($remoteTemplates)) {
            return collect($localTemplates)->map(function (array $template, string $key) {
                return [
                    'key' => $key,
                    'label' => $template['label'] ?? $key,
                    'template_id' => $template['template_id'] ?? null,
                    'template_name' => $template['label'] ?? $key,
                    'has_mapping' => true,
                    'source' => 'config',
                ];
            })->values()->all();
        }

        $localByTemplateId = collect($localTemplates)
            ->filter(fn (array $template) => filled($template['template_id'] ?? null))
            ->map(function (array $template, string $key) {
                $template['__key'] = $key;
                return $template;
            })
            ->keyBy(fn (array $template) => (string) $template['template_id']);

        $options = [];
        $seenTemplateIds = [];

        foreach ($remoteTemplates as $remoteTemplate) {
            $templateId = (string) ($remoteTemplate['template_id'] ?? '');
            if ($templateId === '') {
                continue;
            }

            $localTemplate = $localByTemplateId->get($templateId);

            $options[] = [
                'key' => $localTemplate['__key'] ?? $templateId,
                'label' => $remoteTemplate['label'],
                'template_id' => $templateId,
                'template_name' => $remoteTemplate['template_name'],
                'has_mapping' => (bool) $localTemplate,
                'source' => $localTemplate ? 'config' : 'remote',
            ];

            $seenTemplateIds[$templateId] = true;
        }

        foreach ($localTemplates as $key => $template) {
            $templateId = (string) ($template['template_id'] ?? '');
            if ($templateId !== '' && isset($seenTemplateIds[$templateId])) {
                continue;
            }

            $options[] = [
                'key' => $key,
                'label' => $template['label'] ?? $key,
                'template_id' => $templateId !== '' ? $templateId : null,
                'template_name' => $template['label'] ?? $key,
                'has_mapping' => true,
                'source' => 'config',
            ];
        }

        return $options;
    }

    protected function listRemoteTemplates(): array
    {
        $templates = [];
        $page = 1;
        $maxPages = 10;

        do {
            $response = $this->request()
                ->get('/v1/template/list', [
                    'page' => $page,
                    'pageSize' => 100,
                ])
                ->throw()
                ->json();

            $rows = (array) ($response['result'] ?? []);
            foreach ($rows as $row) {
                $normalized = $this->normalizeTemplateRecord((array) $row);
                if ($normalized) {
                    $templates[] = $normalized;
                }
            }

            $pageDetails = (array) ($response['pageDetails'] ?? []);
            $totalPages = (int) ($pageDetails['totalPages'] ?? $page);

            if ($page >= $totalPages) {
                break;
            }

            $page++;
        } while ($page <= $maxPages);

        return $templates;
    }

    protected function normalizeTemplateRecord(array $row): ?array
    {
        $templateId = trim((string) ($row['templateId'] ?? $row['documentId'] ?? $row['id'] ?? ''));
        if ($templateId === '') {
            return null;
        }

        $templateName = trim((string) ($row['templateName'] ?? $row['messageTitle'] ?? $row['title'] ?? $templateId));

        return [
            'key' => $templateId,
            'label' => $templateName,
            'template_id' => $templateId,
            'template_name' => $templateName,
        ];
    }

    protected function getTemplateProperties(string $templateId): array
    {
        return $this->request()
            ->get('/v1/template/properties', [
                'templateId' => $templateId,
            ])
            ->throw()
            ->json();
    }

    protected function resolveTemplateRole(array $templateProperties, ?array $templateConfig): array
    {
        $roles = collect((array) ($templateProperties['roles'] ?? []));
        if ($roles->isEmpty()) {
            throw new RuntimeException('BoldSign template has no roles.');
        }

        if (filled($templateConfig['signer_role_index'] ?? null)) {
            $match = $roles->first(function (array $role) use ($templateConfig) {
                return (int) ($role['index'] ?? $role['roleIndex'] ?? 0) === (int) $templateConfig['signer_role_index'];
            });

            if ($match) {
                return $match;
            }
        }

        if (filled($templateConfig['signer_role'] ?? null)) {
            $expectedRole = mb_strtolower(trim((string) $templateConfig['signer_role']));

            $match = $roles->first(function (array $role) use ($expectedRole) {
                $name = mb_strtolower(trim((string) ($role['name'] ?? $role['role'] ?? '')));
                return $name !== '' && $name === $expectedRole;
            });

            if ($match) {
                return $match;
            }
        }

        if ($roles->count() > 1) {
            throw new RuntimeException(
                'This BoldSign template has multiple roles. Add a template override with preferred_signer_role or preferred_signer_role_index.'
            );
        }

        return $roles->first();
    }

    protected function buildExistingFormFields(array $leadData, ?array $templateConfig): array
    {
        $fieldMap = (array) ($templateConfig['field_map'] ?? []);
        $fields = [];

        foreach ($fieldMap as $leadField => $boldSignFieldId) {
            $value = $leadData[$leadField] ?? null;
            if ($value === null || $value === '') {
                continue;
            }

            $fields[] = [
                'id' => (string) $boldSignFieldId,
                'value' => (string) $value,
            ];
        }

        return $fields;
    }
}
