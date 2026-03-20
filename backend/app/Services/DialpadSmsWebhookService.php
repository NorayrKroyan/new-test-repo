<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class DialpadSmsWebhookService
{
    public function handle(string $rawBody): array
    {
        $rawBody = trim($rawBody);

        $receiptId = DB::table('dialpad_sms_webhook_receipts')->insertGetId([
            'source' => 'dialpad_sms_webhook',
            'delivery_format' => $this->detectDeliveryFormat($rawBody),
            'raw_body' => $rawBody !== '' ? $rawBody : null,
            'jwt_token' => $this->looksLikeJwt($rawBody) ? $rawBody : null,
            'processed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $payload = $this->decodePayload($rawBody);
            $externalMessageId = $this->resolveExternalMessageId($payload);
            $leadId = $this->matchLeadId($payload);

            $resultStatus = 'stored_and_decoded';
            $smsRecord = null;

            if ($externalMessageId !== null) {
                $smsRecord = $this->upsertSmsRow($payload, $leadId, $externalMessageId);
                $resultStatus = 'processed';
            }

            DB::table('dialpad_sms_webhook_receipts')
                ->where('id', $receiptId)
                ->update([
                    'external_message_id' => $externalMessageId,
                    'lead_id' => $leadId,
                    'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
                    'processed' => true,
                    'processing_error' => null,
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);

            return [
                'status' => $resultStatus,
                'receipt_id' => $receiptId,
                'delivery_format' => $this->detectDeliveryFormat($rawBody),
                'decoded' => true,
                'external_message_id' => $externalMessageId,
                'lead_id' => $leadId,
                'message_status' => $smsRecord['message_status'] ?? null,
                'processing_error' => null,
            ];
        } catch (Throwable $e) {
            DB::table('dialpad_sms_webhook_receipts')
                ->where('id', $receiptId)
                ->update([
                    'processed' => false,
                    'processing_error' => $e->getMessage(),
                    'processed_at' => now(),
                    'updated_at' => now(),
                ]);

            throw $e;
        }
    }

    private function upsertSmsRow(array $payload, ?int $leadId, string $externalMessageId): array
    {
        $now = now();
        $direction = strtolower(trim((string) ($payload['direction'] ?? '')));
        $direction = in_array($direction, ['inbound', 'outbound'], true) ? $direction : null;

        $toNumbers = $this->normalizePhoneList($payload['to_number'] ?? []);
        $fromNumber = $this->normalizePhoneE164($payload['from_number'] ?? null);
        $messageCreatedAt = $this->parseDialpadDate($payload['created_date'] ?? null);

        $text = $this->firstNonEmptyString([
            $payload['text'] ?? null,
            $payload['text_content'] ?? null,
            $payload['message_content'] ?? null,
        ]);

        $contactPhone = $this->normalizePhoneE164(
            data_get($payload, 'contact.phone_number')
            ?? ($direction === 'inbound' ? $fromNumber : ($toNumbers[0] ?? null))
        );

        $targetPhone = $this->normalizePhoneE164(
            data_get($payload, 'target.phone_number')
            ?? ($toNumbers[0] ?? null)
        );

        $record = [
            'lead_id' => $leadId,
            'source' => 'dialpad_sms_webhook',
            'external_message_id' => $externalMessageId,
            'direction' => $direction,
            'message_status' => $this->firstNonEmptyString([
                $payload['message_status'] ?? null,
                $payload['status'] ?? null,
            ]),
            'message_delivery_result' => $this->firstNonEmptyString([
                $payload['message_delivery_result'] ?? null,
                $payload['delivery_result'] ?? null,
            ]),
            'message_created_at' => $messageCreatedAt,
            'target_type' => $this->nullableString(data_get($payload, 'target.type')),
            'target_id' => $this->nullableString(data_get($payload, 'target.id')),
            'target_name' => $this->nullableString(data_get($payload, 'target.name')),
            'target_phone' => $targetPhone,
            'contact_id' => $this->nullableString(data_get($payload, 'contact.id')),
            'contact_name' => $this->nullableString(data_get($payload, 'contact.name')),
            'contact_phone' => $contactPhone,
            'sender_id' => $this->nullableString($payload['sender_id'] ?? null),
            'from_number' => $fromNumber,
            'to_numbers_json' => !empty($toNumbers) ? json_encode($toNumbers, JSON_UNESCAPED_SLASHES) : null,
            'is_mms' => $this->resolveIsMms($payload),
            'text' => $text,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            'webhook_received_at' => $now,
            'updated_at' => $now,
        ];

        $existing = DB::table('lead_sms_histories')
            ->where('source', 'dialpad_sms_webhook')
            ->where('external_message_id', $externalMessageId)
            ->first();

        if ($existing) {
            $updatePayload = $record;
            unset($updatePayload['source'], $updatePayload['external_message_id']);

            DB::table('lead_sms_histories')
                ->where('id', $existing->id)
                ->update($updatePayload);

            return array_merge((array) $existing, $record);
        }

        DB::table('lead_sms_histories')->insert($record + [
                'created_at' => $now,
            ]);

        return $record;
    }

    private function decodePayload(string $rawBody): array
    {
        if ($rawBody === '') {
            throw new RuntimeException('Dialpad SMS webhook body is empty.');
        }

        if ($this->looksLikeQuotedJwt($rawBody)) {
            $decodedString = json_decode($rawBody, true);
            if (is_string($decodedString)) {
                $rawBody = $decodedString;
            }
        }

        if ($this->looksLikeJwt($rawBody)) {
            return $this->decodeJwtPayload($rawBody);
        }

        $decoded = json_decode($rawBody, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('Dialpad SMS webhook body is not valid JSON or JWT.');
        }

        return $decoded;
    }

    private function decodeJwtPayload(string $jwt): array
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            throw new RuntimeException('Dialpad JWT webhook payload is malformed.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (!is_array($header) || !is_array($payload)) {
            throw new RuntimeException('Dialpad JWT webhook payload could not be decoded.');
        }

        $secret = $this->webhookSecret();

        if ($secret === '') {
            return $payload;
        }

        $algorithm = strtoupper((string) ($header['alg'] ?? ''));
        if ($algorithm !== 'HS256') {
            throw new RuntimeException('Unsupported Dialpad webhook JWT algorithm: ' . $algorithm);
        }

        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', $encodedHeader . '.' . $encodedPayload, $secret, true)
        );

        if (!hash_equals($expectedSignature, $encodedSignature)) {
            throw new RuntimeException('Dialpad webhook JWT signature verification failed.');
        }

        return $payload;
    }

    private function resolveExternalMessageId(array $payload): ?string
    {
        $id = $this->firstNonEmptyString([
            $payload['id'] ?? null,
            $payload['message_id'] ?? null,
            $payload['sms_id'] ?? null,
            data_get($payload, 'message.id'),
            data_get($payload, 'sms.id'),
        ]);

        return $id !== '' ? $id : null;
    }

    private function matchLeadId(array $payload): ?int
    {
        $candidates = $this->leadPhoneCandidates($payload);

        foreach ($candidates as $candidate) {
            $matchedLeadId = $this->matchLeadIdByPhone($candidate);

            if ($matchedLeadId !== null) {
                return $matchedLeadId;
            }
        }

        return null;
    }

    private function leadPhoneCandidates(array $payload): array
    {
        $direction = strtolower(trim((string) ($payload['direction'] ?? '')));
        $candidates = [];

        $contactPhone = $this->normalizePhoneE164(data_get($payload, 'contact.phone_number'));
        if ($contactPhone) {
            $candidates[] = $contactPhone;
        }

        $fromNumber = $this->normalizePhoneE164($payload['from_number'] ?? null);
        if ($fromNumber) {
            $candidates[] = $fromNumber;
        }

        foreach ($this->normalizePhoneList($payload['to_number'] ?? []) as $number) {
            $candidates[] = $number;
        }

        if ($direction === 'outbound' && $contactPhone) {
            array_unshift($candidates, $contactPhone);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function matchLeadIdByPhone(string $phone): ?int
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return null;
        }

        $lastTen = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        $leads = Lead::query()
            ->select(['id', 'phone', 'duplicate_of_lead_id'])
            ->whereNotNull('phone')
            ->where('phone', 'like', '%' . $lastTen . '%')
            ->get()
            ->filter(fn (Lead $lead) => $this->normalizePhoneE164($lead->phone) === $phone)
            ->values();

        if ($leads->count() === 1) {
            return (int) $leads->first()->id;
        }

        $activeLeads = $leads->filter(fn (Lead $lead) => !$lead->duplicate_of_lead_id)->values();

        if ($activeLeads->count() === 1) {
            return (int) $activeLeads->first()->id;
        }

        return null;
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
        } catch (Throwable) {
            return null;
        }
    }

    private function normalizePhoneList(mixed $value): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_unique(array_filter(array_map(
            fn ($item) => $this->normalizePhoneE164($item),
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

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text !== '' ? $text : null;
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

    private function resolveIsMms(array $payload): bool
    {
        if (array_key_exists('mms', $payload)) {
            return (bool) $payload['mms'];
        }

        if (!empty($payload['mms_url'] ?? null)) {
            return true;
        }

        return false;
    }

    private function looksLikeJwt(string $value): bool
    {
        return preg_match('/^[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+\.[A-Za-z0-9\-_]+$/', $value) === 1;
    }

    private function looksLikeQuotedJwt(string $value): bool
    {
        return str_starts_with($value, '"') && str_ends_with($value, '"');
    }

    private function detectDeliveryFormat(string $rawBody): string
    {
        if ($this->looksLikeQuotedJwt($rawBody)) {
            $decodedString = json_decode($rawBody, true);
            if (is_string($decodedString) && $this->looksLikeJwt($decodedString)) {
                return 'jwt';
            }
        }

        return $this->looksLikeJwt($rawBody) ? 'jwt' : 'json';
    }

    private function webhookSecret(): string
    {
        return trim((string) (
            config('services.dialpad.webhook_secret')
            ?? config('services.dialpad.secret')
            ?? env('DIALPAD_WEBHOOK_SECRET')
        ));
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url segment in Dialpad webhook payload.');
        }

        return $decoded;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
