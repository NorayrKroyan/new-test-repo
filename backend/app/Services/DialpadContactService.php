<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DialpadContactService
{
    public function isConfigured(): bool
    {
        return filled(config('services.dialpad.token'));
    }

    public function syncLead(Lead $lead): array
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Dialpad integration is not configured.');
        }

        if (!$this->leadHasSyncableIdentity($lead)) {
            throw new RuntimeException('Lead must have at least one of name, phone, or email to sync.');
        }

        $payload = $this->buildPayload($lead);
        $response = Http::baseUrl($this->baseUrl())
            ->withToken((string) config('services.dialpad.token'))
            ->acceptJson()
            ->timeout((int) config('services.dialpad.timeout', 15))
            ->put('contacts', $payload);

        if ($response->failed()) {
            throw new RuntimeException($this->extractErrorMessage($response));
        }

        $data = $response->json();

        return [
            'uid' => $payload['uid'],
            'contact_id' => data_get($data, 'contact.id') ?? data_get($data, 'id'),
            'response' => $data,
        ];
    }

    private function buildPayload(Lead $lead): array
    {
        [$firstName, $lastName] = $this->splitName($lead->full_name);
        $email = $this->normalizeEmail($lead->email);
        $phone = $this->normalizePhoneE164($lead->phone);

        return collect([
            'uid' => $this->uidForLead($lead),
            'display_name' => $this->displayName($lead),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'primary_phone' => $phone,
            'phones' => $phone ? [$phone] : [],
            'primary_email' => $email,
            'emails' => $email ? [$email] : [],
        ])->reject(fn ($value) => $value === null)->all();
    }

    private function uidForLead(Lead $lead): string
    {
        return 'multimodal-team-lead-' . $lead->id;
    }

    private function displayName(Lead $lead): string
    {
        $name = trim((string) $lead->full_name);

        return $name !== '' ? $name : 'Lead #' . $lead->id;
    }

    private function leadHasSyncableIdentity(Lead $lead): bool
    {
        return filled($lead->full_name) || filled($lead->email) || filled($lead->phone);
    }

    private function splitName(?string $value): array
    {
        $value = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');

        if ($value === '') {
            return [null, null];
        }

        $parts = explode(' ', $value, 2);

        return [
            $parts[0] ?? null,
            $parts[1] ?? null,
        ];
    }

    private function normalizeEmail(?string $value): ?string
    {
        $value = mb_strtolower(trim((string) $value));

        return $value !== '' ? $value : null;
    }

    private function normalizePhoneE164(?string $value): ?string
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

    private function baseUrl(): string
    {
        return rtrim((string) config('services.dialpad.base_url', 'https://dialpad.com/api/v2'), '/') . '/';
    }

    private function extractErrorMessage(Response $response): string
    {
        $json = $response->json();

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

        $body = trim((string) $response->body());

        if ($body !== '') {
            return 'Dialpad sync failed: ' . $body;
        }

        return 'Dialpad sync failed with HTTP ' . $response->status() . '.';
    }
}
