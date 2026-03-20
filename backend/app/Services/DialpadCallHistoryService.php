<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DialpadCallHistoryService
{
    public function isConfigured(): bool
    {
        return filled(config('services.dialpad.token'));
    }

    public function syncLead(Lead $lead): array
    {
        return $this->syncLeadInternal($lead, 1);
    }

    public function syncLeadDeep(Lead $lead, int $maxPages = 250): array
    {
        return $this->syncLeadInternal($lead, $maxPages);
    }

    private function syncLeadInternal(Lead $lead, int $maxPages = 1): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Dialpad call history sync is not configured.');
        }

        $phone = $this->normalizePhoneE164($lead->phone);
        $name = $this->normalizeName($lead->full_name);

        if (!$phone && !$name) {
            throw new RuntimeException('Lead must have a phone or name before Dialpad call history can be matched.');
        }

        $maxPages = max(1, (int) $maxPages);
        $cursor = null;
        $pagesScanned = 0;
        $matched = [];
        $imported = 0;
        $updated = 0;

        do {
            $pagesScanned++;

            $query = [];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }

            $response = Http::baseUrl($this->baseUrl())
                ->withToken((string) config('services.dialpad.token'))
                ->acceptJson()
                ->timeout((int) config('services.dialpad.timeout', 15))
                ->get('call', $query);

            if ($response->failed()) {
                throw new RuntimeException(
                    $this->extractErrorMessage(
                        $response->status(),
                        $response->json(),
                        (string) $response->body()
                    )
                );
            }

            $payload = $response->json();
            $items = $this->extractCallItems($payload);

            foreach ($items as $item) {
                if (!$this->matchesLead($item, $phone, $name)) {
                    continue;
                }

                $matched[] = $item;
                $rowPayload = $this->mapCallRow($lead, $item, $phone);

                $existing = DB::table('lead_call_histories')
                    ->where('lead_id', $lead->id)
                    ->where('source', 'dialpad_api')
                    ->where('external_call_id', $rowPayload['external_call_id'])
                    ->first();

                if ($existing) {
                    $updatePayload = $rowPayload;
                    unset($updatePayload['created_at']);

                    DB::table('lead_call_histories')
                        ->where('id', $existing->id)
                        ->update($updatePayload);

                    $updated++;
                } else {
                    DB::table('lead_call_histories')->insert($rowPayload);
                    $imported++;
                }
            }

            $cursor = $this->extractCursor($payload);
        } while ($cursor && $pagesScanned < $maxPages);

        return [
            'status' => 'synced',
            'matched_count' => count($matched),
            'imported_count' => $imported,
            'updated_count' => $updated,
            'pages_scanned' => $pagesScanned,
            'has_more' => filled($cursor),
            'message' => count($matched)
                ? "Dialpad call history synced ({$imported} new, {$updated} updated, {$pagesScanned} page scanned)."
                : 'Dialpad returned no recent calls matching this lead.',
        ];
    }

    private function extractCursor(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $cursor = data_get($payload, 'cursor');

        return is_string($cursor) && trim($cursor) !== '' ? trim($cursor) : null;
    }

    private function extractCallItems(mixed $payload): array
    {
        if (!is_array($payload)) {
            return [];
        }

        foreach (['items', 'calls', 'data'] as $key) {
            $value = data_get($payload, $key);

            if (is_array($value)) {
                return array_values(array_filter($value, 'is_array'));
            }
        }

        if (array_is_list($payload)) {
            return array_values(array_filter($payload, 'is_array'));
        }

        return [];
    }

    private function matchesLead(array $call, ?string $leadPhone, string $leadName): bool
    {
        if ($leadPhone) {
            foreach ($this->callPhoneCandidates($call) as $candidate) {
                if ($candidate === $leadPhone) {
                    return true;
                }
            }
        }

        if ($leadName !== '') {
            foreach ($this->callNameCandidates($call) as $candidate) {
                if ($candidate === $leadName) {
                    return true;
                }
            }
        }

        return false;
    }

    private function mapCallRow(Lead $lead, array $call, ?string $leadPhone): array
    {
        $now = now();

        $direction = strtolower(trim((string) (data_get($call, 'direction') ?? '')));
        $direction = in_array($direction, ['inbound', 'outbound'], true) ? $direction : null;

        $contactPhone = $this->firstNonEmptyPhone([
            data_get($call, 'contact.phone'),
            data_get($call, 'contact.phone_number'),
            data_get($call, 'contact.number'),
            data_get($call, 'external_number'),
            data_get($call, 'customer_phone_number'),
            data_get($call, 'phone_number'),
            data_get($call, 'to_number'),
            data_get($call, 'from_number'),
        ]);

        $internalPhone = $this->firstNonEmptyPhone([
            data_get($call, 'internal_number'),
            data_get($call, 'target.phone_number'),
            data_get($call, 'target.phone'),
            data_get($call, 'user.phone_number'),
            data_get($call, 'owner.phone_number'),
        ]);

        $fromNumber = $direction === 'inbound'
            ? ($contactPhone ?: $this->normalizePhoneE164(data_get($call, 'from_number')))
            : ($internalPhone ?: $this->normalizePhoneE164(data_get($call, 'from_number')));

        $toNumber = $direction === 'inbound'
            ? ($internalPhone ?: $this->normalizePhoneE164(data_get($call, 'to_number')))
            : ($contactPhone ?: $this->normalizePhoneE164(data_get($call, 'to_number')));

        if ($leadPhone) {
            if (!$fromNumber && $direction === 'inbound') {
                $fromNumber = $leadPhone;
            }

            if (!$toNumber && $direction === 'outbound') {
                $toNumber = $leadPhone;
            }
        }

        $startedAt = $this->parseDialpadDate(
            data_get($call, 'date_started')
            ?? data_get($call, 'started_at')
            ?? data_get($call, 'date_rang')
            ?? data_get($call, 'created_date')
        );

        $endedAt = $this->parseDialpadDate(
            data_get($call, 'date_ended')
            ?? data_get($call, 'ended_at')
        );

        $recordingUrl = data_get($call, 'recording_url')
            ?? data_get($call, 'recording_urls.0.url')
            ?? data_get($call, 'recording_urls.0')
            ?? data_get($call, 'recordings.0.url');

        return [
            'lead_id' => $lead->id,
            'source' => 'dialpad_api',
            'external_call_id' => (string) ($this->callExternalId($call)),
            'direction' => $direction,
            'call_status' => $this->normalizeStatus(data_get($call, 'state') ?? data_get($call, 'status')),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'duration_seconds' => $this->normalizeDurationSeconds(
                data_get($call, 'duration'),
                $startedAt,
                $endedAt
            ),
            'agent_name' => $this->firstNonEmptyString([
                data_get($call, 'target.name'),
                data_get($call, 'user.name'),
                data_get($call, 'owner.name'),
                data_get($call, 'operator.name'),
            ]),
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'recording_url' => is_string($recordingUrl) && trim($recordingUrl) !== '' ? trim($recordingUrl) : null,
            'note' => 'Imported from Dialpad Call API.',
            'payload_json' => json_encode($call),
            'updated_at' => $now,
            'created_at' => $now,
        ];
    }

    private function callExternalId(array $call): string
    {
        $value = data_get($call, 'id')
            ?? data_get($call, 'call_id')
            ?? data_get($call, 'operator_call_id')
            ?? data_get($call, 'master_call_id');

        if ($value !== null && trim((string) $value) !== '') {
            return (string) $value;
        }

        return sha1(json_encode($call));
    }

    private function callPhoneCandidates(array $call): array
    {
        $values = [
            data_get($call, 'contact.phone'),
            data_get($call, 'contact.phone_number'),
            data_get($call, 'contact.number'),
            data_get($call, 'external_number'),
            data_get($call, 'customer_phone_number'),
            data_get($call, 'phone_number'),
            data_get($call, 'from_number'),
            data_get($call, 'to_number'),
        ];

        return array_values(array_unique(array_filter(array_map(
            fn ($value) => $this->normalizePhoneE164($value),
            $values
        ))));
    }

    private function callNameCandidates(array $call): array
    {
        $values = [
            data_get($call, 'contact.name'),
            data_get($call, 'contact.full_name'),
            data_get($call, 'customer_name'),
            data_get($call, 'name'),
            data_get($call, 'external_display_name'),
        ];

        return array_values(array_unique(array_filter(array_map(
            fn ($value) => $this->normalizeName($value),
            $values
        ))));
    }

    private function normalizePhoneE164(mixed $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 10) {
            return '+1' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return '+' . $digits;
        }

        return '+' . $digits;
    }

    private function normalizeName(mixed $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $value) ?? ''));
    }

    private function parseDialpadDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $number = (int) $value;

            if ($number > 1000000000000) {
                return Carbon::createFromTimestampMs($number)->utc()->toDateTimeString();
            }

            if ($number > 1000000000) {
                return Carbon::createFromTimestampUTC($number)->toDateTimeString();
            }
        }

        try {
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeDurationSeconds(mixed $value, ?string $startedAt = null, ?string $endedAt = null): ?int
    {
        $rangeSeconds = $this->durationFromDateRange($startedAt, $endedAt);

        if ($value === null || $value === '') {
            return $rangeSeconds;
        }

        if (!is_numeric($value)) {
            return $rangeSeconds;
        }

        $number = (int) round((float) $value);

        if ($number <= 0) {
            return $rangeSeconds;
        }

        if ($rangeSeconds !== null) {
            $candidates = [$number];

            if ($number >= 1000) {
                $millisecondsToSeconds = (int) floor($number / 1000);

                if ($millisecondsToSeconds > 0) {
                    $candidates[] = $millisecondsToSeconds;
                }
            }

            $best = null;
            $bestDelta = null;

            foreach (array_unique($candidates) as $candidate) {
                $delta = abs($candidate - $rangeSeconds);

                if ($best === null || $delta < $bestDelta) {
                    $best = $candidate;
                    $bestDelta = $delta;
                }
            }

            return $best;
        }

        if ($number >= 100000) {
            return (int) floor($number / 1000);
        }

        return $number;
    }

    private function durationFromDateRange(?string $startedAt, ?string $endedAt): ?int
    {
        if (!$startedAt || !$endedAt) {
            return null;
        }

        try {
            $start = Carbon::parse($startedAt);
            $end = Carbon::parse($endedAt);
        } catch (\Throwable) {
            return null;
        }

        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        $seconds = $start->diffInSeconds($end);

        return $seconds > 0 ? $seconds : null;
    }

    private function normalizeStatus(mixed $value): ?string
    {
        $status = strtolower(trim((string) $value));

        return $status !== '' ? $status : null;
    }

    private function firstNonEmptyPhone(array $values): ?string
    {
        foreach ($values as $value) {
            $normalized = $this->normalizePhoneE164($value);

            if ($normalized) {
                return $normalized;
            }
        }

        return null;
    }

    private function firstNonEmptyString(array $values): ?string
    {
        foreach ($values as $value) {
            $text = trim((string) $value);

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.dialpad.base_url', 'https://dialpad.com/api/v2'), '/') . '/';
    }

    private function extractErrorMessage(int $status, mixed $json, string $body): string
    {
        if (is_array($json)) {
            $message = data_get($json, 'message')
                ?? data_get($json, 'error.message')
                ?? data_get($json, 'error')
                ?? data_get($json, 'errors.0.message')
                ?? data_get($json, 'errors.0');

            if (is_string($message) && trim($message) !== '') {
                return trim($message);
            }
        }

        $body = trim($body);

        if ($body !== '') {
            return $body;
        }

        return 'Dialpad call history sync failed with HTTP ' . $status . '.';
    }
}
