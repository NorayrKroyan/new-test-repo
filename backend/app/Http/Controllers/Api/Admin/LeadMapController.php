<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GeocodeLeadMapCampaignJob;
use App\Models\Lead;
use App\Services\LeadMapGeocodingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LeadMapController extends Controller
{
    public function __construct(
        protected LeadMapGeocodingService $geocoder
    ) {
    }

    public function markers(Request $request)
    {
        $adName = trim((string) $request->query('ad_name', ''));
        $scope = $this->normalizeScope($request->query('scope'));
        $q = trim((string) $request->query('q', ''));
        $stageId = (int) $request->query('stage_id', 0);
        $jobState = $this->getJobState($adName, $scope, $q, $stageId);

        if ($adName === '') {
            return response()->json([
                'data' => [],
                'meta' => [
                    'ad_name' => null,
                    'scope' => $scope,
                    'counts' => [
                        'total' => 0,
                        'mapped' => 0,
                        'unmapped' => 0,
                    ],
                    'unresolved' => [],
                    'processing' => false,
                    'processing_status' => null,
                    'processing_message' => null,
                ],
            ]);
        }

        $rows = $this->baseLeadQuery($adName, $scope, $q, $stageId)
            ->orderByDesc('leads.id')
            ->get();

        $mapped = $rows->filter(fn (Lead $lead) => $lead->map_lat !== null && $lead->map_lng !== null)->values();
        $unresolved = $rows->reject(fn (Lead $lead) => $lead->map_lat !== null && $lead->map_lng !== null)->values();

        return response()->json([
            'data' => $mapped->map(fn (Lead $lead) => $this->serializeMarker($lead))->values(),
            'meta' => [
                'ad_name' => $adName,
                'scope' => $scope,
                'counts' => [
                    'total' => (int) $rows->count(),
                    'mapped' => (int) $mapped->count(),
                    'unmapped' => (int) $unresolved->count(),
                ],
                'unresolved' => $unresolved
                    ->take(75)
                    ->map(fn (Lead $lead) => $this->serializeUnresolvedLead($lead))
                    ->values(),
                'processing' => in_array((string) ($jobState['status'] ?? ''), ['queued', 'running'], true),
                'processing_status' => $jobState['status'] ?? null,
                'processing_message' => $jobState['message'] ?? null,
                'processing_started_at' => $jobState['started_at'] ?? null,
                'processing_completed_at' => $jobState['completed_at'] ?? null,
                'processing_processed' => (int) ($jobState['processed'] ?? 0),
                'processing_ok' => (int) ($jobState['ok'] ?? 0),
                'processing_errors' => (int) ($jobState['errors'] ?? 0),
            ],
        ]);
    }

    public function geocodeMissing(Request $request)
    {
        $data = $request->validate([
            'ad_name' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
            'q' => ['nullable', 'string'],
            'stage_id' => ['nullable', 'integer'],
            'force' => ['nullable', 'boolean'],
        ]);

        $adName = trim((string) $data['ad_name']);
        $scope = $this->normalizeScope($data['scope'] ?? null);
        $q = trim((string) ($data['q'] ?? ''));
        $stageId = (int) ($data['stage_id'] ?? 0);
        $force = (bool) ($data['force'] ?? false);
        $cacheKey = $this->buildGeocodeCacheKey($adName, $scope, $q, $stageId);

        $existing = Cache::get($cacheKey);

        if (is_array($existing) && in_array((string) ($existing['status'] ?? ''), ['queued', 'running'], true)) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'queued' => false,
                    'already_running' => true,
                    'processing' => true,
                    'processing_status' => $existing['status'] ?? 'running',
                    'processing_message' => $existing['message'] ?? 'Background geocoding is already running.',
                ],
            ], 202);
        }

        Cache::put($cacheKey, [
            'status' => 'queued',
            'message' => 'Background geocoding is queued.',
            'started_at' => now()->toDateTimeString(),
            'completed_at' => null,
            'processed' => 0,
            'ok' => 0,
            'errors' => 0,
        ], now()->addHours(2));

        GeocodeLeadMapCampaignJob::dispatch(
            adName: $adName,
            scope: $scope,
            q: $q,
            stageId: $stageId,
            force: $force,
            cacheKey: $cacheKey,
        );

        return response()->json([
            'data' => [],
            'meta' => [
                'queued' => true,
                'already_running' => false,
                'processing' => true,
                'processing_status' => 'queued',
                'processing_message' => 'Background geocoding has been queued.',
            ],
        ], 202);
    }

    public function geocodeLead(Request $request, Lead $lead)
    {
        $force = $request->boolean('force');

        return response()->json([
            'data' => $this->geocoder->geocodeLead($lead, $force),
        ]);
    }

    private function baseLeadQuery(string $adName, string $scope, string $q, int $stageId): Builder
    {
        $query = Lead::query()
            ->select([
                'leads.*',
                'lead_map_points.id as map_point_id',
                'lead_map_points.query_source as map_query_source',
                'lead_map_points.geocode_query as map_geocode_query',
                'lead_map_points.resolved_city as map_resolved_city',
                'lead_map_points.resolved_state as map_resolved_state',
                'lead_map_points.resolved_postal_code as map_resolved_postal_code',
                'lead_map_points.formatted_address as map_formatted_address',
                'lead_map_points.place_id as map_place_id',
                'lead_map_points.lat as map_lat',
                'lead_map_points.lng as map_lng',
                'lead_map_points.geocode_status as map_geocode_status',
                'lead_map_points.geocoded_at as map_geocoded_at',
                'lead_map_points.last_error as map_last_error',
            ])
            ->with([
                'stage:id,stage_name,stage_group,stage_order',
            ])
            ->leftJoin('lead_map_points', 'lead_map_points.lead_id', '=', 'leads.id')
            ->where('leads.ad_name', $adName);

        $this->applyScope($query, $scope);
        $this->applySearch($query, $q);

        if ($stageId > 0) {
            $query->where('leads.lead_stage_id', $stageId);
        }

        return $query;
    }

    private function applyScope(Builder $query, string $scope): void
    {
        if ($scope === 'duplicates') {
            $query->whereNotNull('leads.duplicate_of_lead_id');
            return;
        }

        if ($scope === 'active') {
            $query->whereNull('leads.duplicate_of_lead_id');
        }
    }

    private function applySearch(Builder $query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $query->where(function (Builder $subQuery) use ($q) {
            $subQuery->where('leads.full_name', 'like', "%{$q}%")
                ->orWhere('leads.email', 'like', "%{$q}%")
                ->orWhere('leads.phone', 'like', "%{$q}%")
                ->orWhere('leads.city', 'like', "%{$q}%")
                ->orWhere('leads.state', 'like', "%{$q}%")
                ->orWhere('leads.platform', 'like', "%{$q}%")
                ->orWhere('leads.source_name', 'like', "%{$q}%")
                ->orWhere('leads.lead_status', 'like', "%{$q}%");
        });
    }

    private function normalizeScope(mixed $scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return in_array($scope, ['all', 'active', 'duplicates'], true) ? $scope : 'active';
    }

    private function buildGeocodeCacheKey(string $adName, string $scope, string $q, int $stageId): string
    {
        return 'lead-map-geocode:' . md5(json_encode([
                'ad_name' => $adName,
                'scope' => $scope,
                'q' => $q,
                'stage_id' => $stageId,
            ]));
    }

    private function getJobState(string $adName, string $scope, string $q, int $stageId): array
    {
        if ($adName === '') {
            return [];
        }

        $state = Cache::get($this->buildGeocodeCacheKey($adName, $scope, $q, $stageId));

        return is_array($state) ? $state : [];
    }

    private function serializeMarker(Lead $lead): array
    {
        return [
            'id' => (int) $lead->id,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'ad_name' => $lead->ad_name,
            'platform' => $lead->platform,
            'source_name' => $lead->source_name,
            'source_created_at' => $lead->source_created_at,
            'lead_date_choice' => $lead->lead_date_choice,
            'created_at' => $lead->created_at?->toDateTimeString(),
            'city' => $lead->city,
            'state' => $lead->state,
            'resolved_city' => $lead->map_resolved_city,
            'resolved_state' => $lead->map_resolved_state,
            'resolved_postal_code' => $lead->map_resolved_postal_code,
            'formatted_address' => $lead->map_formatted_address,
            'lat' => (float) $lead->map_lat,
            'lng' => (float) $lead->map_lng,
            'lead_status' => $lead->lead_status,
            'lead_stage_id' => $lead->lead_stage_id,
            'lead_href' => '/api/admin/leads/' . $lead->id,
            'lead_ui_href' => '/admin/leads?lead_id=' . $lead->id,
            'stage' => $lead->stage ? [
                'id' => (int) $lead->stage->id,
                'stage_name' => $lead->stage->stage_name,
                'stage_group' => $lead->stage->stage_group,
                'stage_order' => $lead->stage->stage_order,
            ] : null,
        ];
    }

    private function serializeUnresolvedLead(Lead $lead): array
    {
        return [
            'id' => (int) $lead->id,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'ad_name' => $lead->ad_name,
            'platform' => $lead->platform,
            'source_created_at' => $lead->source_created_at,
            'lead_date_choice' => $lead->lead_date_choice,
            'created_at' => $lead->created_at?->toDateTimeString(),
            'city' => $lead->city,
            'state' => $lead->state,
            'resolved_city' => $lead->map_resolved_city,
            'resolved_state' => $lead->map_resolved_state,
            'resolved_postal_code' => $lead->map_resolved_postal_code,
            'formatted_address' => $lead->map_formatted_address,
            'lead_status' => $lead->lead_status,
            'lead_stage_id' => $lead->lead_stage_id,
            'lead_href' => '/api/admin/leads/' . $lead->id,
            'lead_ui_href' => '/admin/leads?lead_id=' . $lead->id,
            'map_query_source' => $lead->map_query_source,
            'map_geocode_query' => $lead->map_geocode_query,
            'map_geocode_status' => $lead->map_geocode_status,
            'map_last_error' => $lead->map_last_error,
            'stage' => $lead->stage ? [
                'id' => (int) $lead->stage->id,
                'stage_name' => $lead->stage->stage_name,
                'stage_group' => $lead->stage->stage_group,
                'stage_order' => $lead->stage->stage_order,
            ] : null,
        ];
    }
}
