<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadMapPoint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class LeadMapGeocodingService
{
    private const PREFERRED_STATE_CODES = [
        'LA',
        'TX',
        'NM',
        'OK',
        'CO',
        'WY',
        'UT',
        'ND',
        'MT',
    ];

    private const UNKNOWN_STATE = 'UNKNOWN';

    public function geocodeLead(Lead $lead, bool $force = false): array
    {
        $point = LeadMapPoint::query()->firstOrNew([
            'lead_id' => $lead->id,
        ]);

        if (
            !$force
            && $point->exists
            && $point->lat !== null
            && $point->lng !== null
            && $point->geocode_status === 'ok'
        ) {
            return $this->serializePoint($lead, $point, 'cached');
        }

        $location = $this->resolveLocationInputs($lead);

        if ($location['query'] === null) {
            $point->fill([
                'query_source' => null,
                'geocode_query' => null,
                'resolved_city' => $location['city'],
                'resolved_state' => $location['state'],
                'resolved_postal_code' => $location['postal_code'],
                'formatted_address' => null,
                'place_id' => null,
                'lat' => null,
                'lng' => null,
                'geocode_status' => 'missing_location',
                'geocoded_at' => null,
                'last_error' => 'No usable city, state, address, or postal code was found on the lead or in raw_payload.',
            ])->save();

            return $this->serializePoint($lead, $point, 'missing_location');
        }

        $apiKey = trim((string) env('GOOGLE_MAPS_GEOCODING_API_KEY', ''));

        if ($apiKey === '') {
            $point->fill([
                'query_source' => $location['query_source'],
                'geocode_query' => $location['query'],
                'resolved_city' => $location['city'],
                'resolved_state' => $location['state'],
                'resolved_postal_code' => $location['postal_code'],
                'formatted_address' => null,
                'place_id' => null,
                'lat' => null,
                'lng' => null,
                'geocode_status' => 'missing_api_key',
                'geocoded_at' => null,
                'last_error' => 'GOOGLE_MAPS_GEOCODING_API_KEY is not configured.',
            ])->save();

            return $this->serializePoint($lead, $point, 'missing_api_key');
        }

        try {
            $response = Http::timeout(8)
                ->acceptJson()
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $location['query'],
                    'components' => 'country:US',
                    'region' => 'us',
                    'key' => $apiKey,
                ]);

            if (!$response->ok()) {
                $point->fill([
                    'query_source' => $location['query_source'],
                    'geocode_query' => $location['query'],
                    'resolved_city' => $location['city'],
                    'resolved_state' => $location['state'],
                    'resolved_postal_code' => $location['postal_code'],
                    'formatted_address' => null,
                    'place_id' => null,
                    'lat' => null,
                    'lng' => null,
                    'geocode_status' => 'request_failed',
                    'geocoded_at' => null,
                    'last_error' => 'HTTP ' . $response->status() . ' from Google Geocoding API.',
                ])->save();

                return $this->serializePoint($lead, $point, 'request_failed');
            }

            $payload = $response->json();
            $status = strtoupper((string) ($payload['status'] ?? 'UNKNOWN'));
            $results = is_array($payload['results'] ?? null) ? $payload['results'] : [];

            if ($status !== 'OK' || count($results) === 0) {
                $point->fill([
                    'query_source' => $location['query_source'],
                    'geocode_query' => $location['query'],
                    'resolved_city' => $location['city'],
                    'resolved_state' => $location['state'],
                    'resolved_postal_code' => $location['postal_code'],
                    'formatted_address' => null,
                    'place_id' => null,
                    'lat' => null,
                    'lng' => null,
                    'geocode_status' => strtolower($status),
                    'geocoded_at' => null,
                    'last_error' => (string) ($payload['error_message'] ?? $status),
                ])->save();

                return $this->serializePoint($lead, $point, strtolower($status));
            }

            if (($location['query_source'] ?? '') === 'city_only') {
                $cityOnlyDecision = $this->resolveCityOnlyResult($results);

                if ($cityOnlyDecision['status'] !== 'ok' || !is_array($cityOnlyDecision['result'])) {
                    $point->fill([
                        'query_source' => $location['query_source'],
                        'geocode_query' => $location['query'],
                        'resolved_city' => $location['city'],
                        'resolved_state' => self::UNKNOWN_STATE,
                        'resolved_postal_code' => $location['postal_code'],
                        'formatted_address' => null,
                        'place_id' => null,
                        'lat' => null,
                        'lng' => null,
                        'geocode_status' => $cityOnlyDecision['status'],
                        'geocoded_at' => null,
                        'last_error' => $cityOnlyDecision['last_error'],
                    ])->save();

                    return $this->serializePoint($lead, $point, $cityOnlyDecision['status']);
                }

                $result = $cityOnlyDecision['result'];
            } else {
                if (($location['state'] ?? '') === '' && count($results) > 1) {
                    $matchedStates = collect($results)
                        ->map(fn ($result) => $this->extractComponent(data_get($result, 'address_components', []), 'administrative_area_level_1', true))
                        ->filter()
                        ->unique()
                        ->values();

                    if ($matchedStates->count() > 1) {
                        $point->fill([
                            'query_source' => $location['query_source'],
                            'geocode_query' => $location['query'],
                            'resolved_city' => $location['city'],
                            'resolved_state' => null,
                            'resolved_postal_code' => $location['postal_code'],
                            'formatted_address' => null,
                            'place_id' => null,
                            'lat' => null,
                            'lng' => null,
                            'geocode_status' => 'ambiguous_city',
                            'geocoded_at' => null,
                            'last_error' => 'This city-only query matched multiple states: ' . $matchedStates->implode(', '),
                        ])->save();

                        return $this->serializePoint($lead, $point, 'ambiguous_city');
                    }
                }

                $result = $results[0];
            }

            $components = data_get($result, 'address_components', []);
            $geometry = data_get($result, 'geometry.location', []);

            if (!isset($geometry['lat'], $geometry['lng'])) {
                $point->fill([
                    'query_source' => $location['query_source'],
                    'geocode_query' => $location['query'],
                    'resolved_city' => $location['city'],
                    'resolved_state' => $location['state'],
                    'resolved_postal_code' => $location['postal_code'],
                    'formatted_address' => null,
                    'place_id' => null,
                    'lat' => null,
                    'lng' => null,
                    'geocode_status' => 'invalid_response',
                    'geocoded_at' => null,
                    'last_error' => 'Geocoding response did not contain a usable geometry.location block.',
                ])->save();

                return $this->serializePoint($lead, $point, 'invalid_response');
            }

            $resolvedCity = $this->extractFirstMatchingComponent($components, [
                'locality',
                'postal_town',
                'administrative_area_level_2',
                'sublocality',
                'sublocality_level_1',
                'neighborhood',
            ]) ?: ($location['city'] ?: null);

            $resolvedState = $this->extractComponent($components, 'administrative_area_level_1', true)
                ?: ($location['state'] ?: null);

            $resolvedPostalCode = $this->extractComponent($components, 'postal_code', true)
                ?: ($location['postal_code'] ?: null);

            $point->fill([
                'query_source' => $location['query_source'],
                'geocode_query' => $location['query'],
                'resolved_city' => $resolvedCity,
                'resolved_state' => $resolvedState,
                'resolved_postal_code' => $resolvedPostalCode,
                'formatted_address' => (string) data_get($result, 'formatted_address', ''),
                'place_id' => (string) data_get($result, 'place_id', ''),
                'lat' => (float) $geometry['lat'],
                'lng' => (float) $geometry['lng'],
                'geocode_status' => 'ok',
                'geocoded_at' => now(),
                'last_error' => null,
            ])->save();

            return $this->serializePoint($lead, $point, 'ok');
        } catch (Throwable $e) {
            $point->fill([
                'query_source' => $location['query_source'],
                'geocode_query' => $location['query'],
                'resolved_city' => $location['city'],
                'resolved_state' => $location['state'],
                'resolved_postal_code' => $location['postal_code'],
                'formatted_address' => null,
                'place_id' => null,
                'lat' => null,
                'lng' => null,
                'geocode_status' => 'exception',
                'geocoded_at' => null,
                'last_error' => $e->getMessage(),
            ])->save();

            return $this->serializePoint($lead, $point, 'exception');
        }
    }

    private function resolveCityOnlyResult(array $results): array
    {
        $candidates = collect($results)
            ->map(function (array $result) {
                return [
                    'state' => $this->extractComponent(
                        data_get($result, 'address_components', []),
                        'administrative_area_level_1',
                        true
                    ),
                    'result' => $result,
                ];
            })
            ->filter(fn (array $candidate) => !empty($candidate['state']))
            ->values();

        if ($candidates->isEmpty()) {
            return [
                'status' => 'unknown_state',
                'result' => null,
                'last_error' => 'This city-only query did not resolve to a usable state. Set to UNKNOWN for manual review.',
            ];
        }

        $matchedStates = $candidates
            ->pluck('state')
            ->filter()
            ->unique()
            ->values();

        $preferredStates = $matchedStates
            ->filter(fn ($state) => in_array((string) $state, self::PREFERRED_STATE_CODES, true))
            ->values();

        if ($preferredStates->count() === 1) {
            $preferredState = (string) $preferredStates->first();

            $selected = $candidates->first(
                fn (array $candidate) => (string) ($candidate['state'] ?? '') === $preferredState
            );

            return [
                'status' => 'ok',
                'result' => is_array($selected) ? ($selected['result'] ?? null) : null,
                'last_error' => null,
            ];
        }

        if ($preferredStates->count() > 1) {
            return [
                'status' => 'ambiguous_city',
                'result' => null,
                'last_error' => 'This city-only query matched multiple preferred operating states: '
                    . $preferredStates->implode(', ')
                    . '. Set to UNKNOWN for manual review.',
            ];
        }

        return [
            'status' => 'unknown_state',
            'result' => null,
            'last_error' => 'This city-only query matched only non-operating states: '
                . $matchedStates->implode(', ')
                . '. Set to UNKNOWN for manual review.',
        ];
    }

    private function resolveLocationInputs(Lead $lead): array
    {
        $city = trim((string) ($lead->city ?? ''));
        $state = trim((string) ($lead->state ?? ''));

        $flat = $this->flattenRawPayload($lead->raw_payload ?? []);

        if ($city === '') {
            $city = $this->firstValueForKeys($flat, [
                    'city',
                    'town',
                    'locality',
                    'current_city',
                    'home_city',
                ]) ?? '';
        }

        if ($state === '') {
            $state = $this->firstValueForKeys($flat, [
                    'state',
                    'province',
                    'region',
                    'state_code',
                    'province_code',
                ]) ?? '';
        }

        $address = $this->firstValueForKeys($flat, [
            'address',
            'street',
            'street_address',
            'mailing_address',
            'location',
        ]);

        $postalCode = $this->firstValueForKeys($flat, [
            'zip',
            'zip_code',
            'zipcode',
            'postal_code',
            'postal',
        ]);

        $city = $this->normalizeText($city);
        $state = $this->normalizeState($state);
        $address = $this->normalizeText($address);
        $postalCode = $this->normalizeText($postalCode);

        if ($city !== '' && $state !== '') {
            return [
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'query' => $city . ', ' . $state,
                'query_source' => 'city_state',
            ];
        }

        if ($city !== '') {
            return [
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'query' => $city,
                'query_source' => 'city_only',
            ];
        }

        if ($address !== '') {
            return [
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'query' => $address,
                'query_source' => 'address',
            ];
        }

        if ($postalCode !== '') {
            return [
                'city' => $city,
                'state' => $state,
                'postal_code' => $postalCode,
                'query' => $postalCode,
                'query_source' => 'postal_code',
            ];
        }

        return [
            'city' => $city,
            'state' => $state,
            'postal_code' => $postalCode,
            'query' => null,
            'query_source' => null,
        ];
    }

    private function flattenRawPayload(mixed $value, string $prefix = ''): array
    {
        $rows = [];

        if (is_array($value)) {
            foreach ($value as $key => $nestedValue) {
                $nextPrefix = $prefix === '' ? (string) $key : $prefix . '.' . $key;
                $rows += $this->flattenRawPayload($nestedValue, $nextPrefix);
            }

            return $rows;
        }

        if (is_scalar($value) || $value === null) {
            $rows[$prefix] = $value;
        }

        return $rows;
    }

    private function firstValueForKeys(array $flat, array $needles): ?string
    {
        foreach ($flat as $key => $value) {
            $normalizedKey = Str::lower((string) $key);

            foreach ($needles as $needle) {
                if (Str::contains($normalizedKey, Str::lower($needle))) {
                    $normalizedValue = $this->normalizeText($value);

                    if ($normalizedValue !== '') {
                        return $normalizedValue;
                    }
                }
            }
        }

        return null;
    }

    private function normalizeText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeState(?string $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        return strlen($value) <= 3 ? strtoupper($value) : $value;
    }

    private function extractComponent(array $components, string $type, bool $useShortName = false): ?string
    {
        foreach ($components as $component) {
            $types = is_array($component['types'] ?? null) ? $component['types'] : [];

            if (in_array($type, $types, true)) {
                $value = (string) ($useShortName
                    ? ($component['short_name'] ?? '')
                    : ($component['long_name'] ?? ''));

                return trim($value) !== '' ? trim($value) : null;
            }
        }

        return null;
    }

    private function extractFirstMatchingComponent(array $components, array $types): ?string
    {
        foreach ($types as $type) {
            $value = $this->extractComponent($components, $type);

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function serializePoint(Lead $lead, LeadMapPoint $point, string $status): array
    {
        return [
            'lead_id' => (int) $lead->id,
            'lead_name' => $lead->full_name,
            'status' => $status,
            'query_source' => $point->query_source,
            'geocode_query' => $point->geocode_query,
            'resolved_city' => $point->resolved_city,
            'resolved_state' => $point->resolved_state,
            'resolved_postal_code' => $point->resolved_postal_code,
            'formatted_address' => $point->formatted_address,
            'place_id' => $point->place_id,
            'lat' => $point->lat !== null ? (float) $point->lat : null,
            'lng' => $point->lng !== null ? (float) $point->lng : null,
            'geocode_status' => $point->geocode_status,
            'geocoded_at' => $point->geocoded_at?->toDateTimeString(),
            'last_error' => $point->last_error,
        ];
    }
}
