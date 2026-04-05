<?php

namespace App\Services;

use App\Models\BoldSignTemplate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class BoldSignTemplateSyncService
{
    public function syncAll(bool $syncDetails = true): array
    {
        $this->ensureConfigured();

        $now = now();
        $warnings = [];
        $created = 0;
        $updated = 0;
        $reactivated = 0;
        $inactivated = 0;
        $seenTemplateIds = [];

        $remoteTemplates = $this->listRemoteTemplates();

        foreach ($remoteTemplates as $remoteTemplate) {
            $templateId = (string) ($remoteTemplate['template_id'] ?? '');
            if ($templateId === '') {
                continue;
            }

            $seenTemplateIds[] = $templateId;

            $detailsPayload = null;
            $rolesJson = $remoteTemplate['roles_json'] ?? null;
            $sharedWithTeamsJson = $remoteTemplate['shared_with_teams_json'] ?? null;
            $templateType = $remoteTemplate['template_type'] ?? null;
            $lastModifiedAt = $remoteTemplate['last_modified_at'] ?? null;

            if ($syncDetails) {
                try {
                    $detailsPayload = $this->getTemplateProperties($templateId);

                    $rolesJson = $this->extractRolesFromDetails($detailsPayload) ?: $rolesJson;
                    $sharedWithTeamsJson = $this->extractSharedTeamsFromDetails($detailsPayload) ?: $sharedWithTeamsJson;
                    $templateType = $this->extractTemplateTypeFromDetails($detailsPayload) ?: $templateType;
                    $lastModifiedAt = $this->extractLastModifiedAtFromDetails($detailsPayload) ?: $lastModifiedAt;
                } catch (Throwable $e) {
                    $warnings[] = 'Template ' . $templateId . ' details sync failed: ' . $e->getMessage();
                }
            }

            /** @var BoldSignTemplate|null $existing */
            $existing = BoldSignTemplate::query()
                ->where('template_id', $templateId)
                ->first();

            $payload = [
                'template_name' => $remoteTemplate['template_name'],
                'created_by_name' => $remoteTemplate['created_by_name'] ?? null,
                'template_type' => $templateType,
                'roles_json' => $rolesJson,
                'shared_with_teams_json' => $sharedWithTeamsJson,
                'raw_payload_json' => array_filter([
                    'list' => $remoteTemplate['raw_payload_json'] ?? null,
                    'details' => $detailsPayload,
                ], static fn ($value) => $value !== null),
                'is_active' => true,
                'last_modified_at' => $lastModifiedAt,
                'last_synced_at' => $now,
            ];

            if (!$existing) {
                BoldSignTemplate::query()->create(array_merge($payload, [
                    'template_id' => $templateId,
                ]));
                $created++;
                continue;
            }

            $wasInactive = !$existing->is_active;

            $existing->fill($payload);
            $existing->save();

            if ($wasInactive) {
                $reactivated++;
            } else {
                $updated++;
            }
        }

        if (!empty($seenTemplateIds)) {
            $inactivated = BoldSignTemplate::query()
                ->whereNotIn('template_id', $seenTemplateIds)
                ->where('is_active', true)
                ->update([
                    'is_active' => false,
                    'last_synced_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'reactivated' => $reactivated,
            'inactivated' => $inactivated,
            'total_remote' => count($remoteTemplates),
            'warnings' => $warnings,
        ];
    }

    protected function ensureConfigured(): void
    {
        $apiKey = (string) config('services.boldsign.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('BoldSign API key is not configured.');
        }
    }

    protected function request()
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

    protected function listRemoteTemplates(): array
    {
        $templates = [];
        $page = 1;
        $maxPages = 20;

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
                $normalized = $this->normalizeTemplateListRow((array) $row);
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

    protected function getTemplateProperties(string $templateId): array
    {
        return $this->request()
            ->get('/v1/template/properties', [
                'templateId' => $templateId,
            ])
            ->throw()
            ->json();
    }

    protected function normalizeTemplateListRow(array $row): ?array
    {
        $templateId = trim((string) ($row['templateId'] ?? $row['documentId'] ?? $row['id'] ?? ''));
        if ($templateId === '') {
            return null;
        }

        $templateName = trim((string) ($row['templateName'] ?? $row['messageTitle'] ?? $row['title'] ?? $templateId));

        return [
            'template_id' => $templateId,
            'template_name' => $templateName,
            'created_by_name' => $this->extractCreatedByNameFromListRow($row),
            'template_type' => $this->extractTemplateTypeFromListRow($row),
            'roles_json' => $this->extractRolesFromListRow($row),
            'shared_with_teams_json' => $this->extractSharedTeamsFromListRow($row),
            'last_modified_at' => $this->extractLastModifiedAtFromListRow($row),
            'raw_payload_json' => $row,
        ];
    }

    protected function extractCreatedByNameFromListRow(array $row): ?string
    {
        $value = $row['createdByName']
            ?? $row['createdBy']
            ?? ($row['createdByUser']['name'] ?? null)
            ?? ($row['createdByUser']['fullName'] ?? null)
            ?? null;

        $value = is_string($value) ? trim($value) : null;

        return $value !== '' ? $value : null;
    }

    protected function extractTemplateTypeFromListRow(array $row): ?string
    {
        $value = $row['templateType'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function extractRolesFromListRow(array $row): ?array
    {
        $roles = $row['roles'] ?? null;

        return is_array($roles) && !empty($roles) ? $roles : null;
    }

    protected function extractSharedTeamsFromListRow(array $row): ?array
    {
        $teams = $row['sharedWithTeams']
            ?? $row['sharedTeams']
            ?? null;

        return is_array($teams) && !empty($teams) ? array_values($teams) : null;
    }

    protected function extractLastModifiedAtFromListRow(array $row): ?Carbon
    {
        return $this->parseNullableDate(
            $row['lastModified']
            ?? $row['lastModifiedOn']
            ?? $row['modifiedDateTime']
            ?? $row['updatedAt']
            ?? null
        );
    }

    protected function extractRolesFromDetails(array $payload): ?array
    {
        $roles = $payload['roles'] ?? null;

        return is_array($roles) && !empty($roles) ? $roles : null;
    }

    protected function extractSharedTeamsFromDetails(array $payload): ?array
    {
        $teams = $payload['sharedWithTeams']
            ?? $payload['sharedTeams']
            ?? null;

        return is_array($teams) && !empty($teams) ? array_values($teams) : null;
    }

    protected function extractTemplateTypeFromDetails(array $payload): ?string
    {
        $value = $payload['templateType'] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    protected function extractLastModifiedAtFromDetails(array $payload): ?Carbon
    {
        return $this->parseNullableDate(
            $payload['lastModified']
            ?? $payload['lastModifiedOn']
            ?? $payload['modifiedDateTime']
            ?? $payload['updatedAt']
            ?? null
        );
    }

    protected function parseNullableDate(mixed $value): ?Carbon
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }
    }
}
