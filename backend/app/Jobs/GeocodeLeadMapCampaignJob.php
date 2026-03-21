<?php

namespace App\Jobs;

use App\Models\Lead;
use App\Services\LeadMapGeocodingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GeocodeLeadMapCampaignJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        public string $adName,
        public string $scope = 'active',
        public string $q = '',
        public int $stageId = 0,
        public bool $force = false,
        public string $cacheKey = '',
    ) {
    }

    public function handle(LeadMapGeocodingService $geocoder): void
    {
        $startedAt = now()->toDateTimeString();
        $processed = 0;
        $ok = 0;
        $errors = 0;

        Cache::put($this->cacheKey, [
            'status' => 'running',
            'message' => 'Background geocoding is running.',
            'started_at' => $startedAt,
            'completed_at' => null,
            'processed' => 0,
            'ok' => 0,
            'errors' => 0,
        ], now()->addHours(2));

        $query = $this->eligibleLeadIdsQuery();

        $query->chunkById(50, function ($rows) use ($geocoder, &$processed, &$ok, &$errors, $startedAt) {
            $ids = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();

            if (!$ids) {
                return;
            }

            $leads = Lead::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->get();

            foreach ($leads as $lead) {
                $result = $geocoder->geocodeLead($lead, $this->force);

                $processed++;

                if (in_array((string) ($result['status'] ?? ''), ['ok', 'cached'], true)) {
                    $ok++;
                } else {
                    $errors++;
                }
            }

            Cache::put($this->cacheKey, [
                'status' => 'running',
                'message' => 'Background geocoding is running.',
                'started_at' => $startedAt,
                'completed_at' => null,
                'processed' => $processed,
                'ok' => $ok,
                'errors' => $errors,
            ], now()->addHours(2));
        }, 'leads.id', 'id');

        Cache::put($this->cacheKey, [
            'status' => 'completed',
            'message' => 'Background geocoding completed.',
            'started_at' => $startedAt,
            'completed_at' => now()->toDateTimeString(),
            'processed' => $processed,
            'ok' => $ok,
            'errors' => $errors,
        ], now()->addMinutes(15));
    }

    public function failed(Throwable $e): void
    {
        Cache::put($this->cacheKey, [
            'status' => 'failed',
            'message' => $e->getMessage(),
            'started_at' => now()->toDateTimeString(),
            'completed_at' => now()->toDateTimeString(),
            'processed' => 0,
            'ok' => 0,
            'errors' => 0,
        ], now()->addMinutes(30));
    }

    private function eligibleLeadIdsQuery(): Builder
    {
        $query = Lead::query()
            ->select(['leads.id'])
            ->leftJoin('lead_map_points', 'lead_map_points.lead_id', '=', 'leads.id')
            ->where('leads.ad_name', $this->adName);

        $this->applyScope($query);
        $this->applySearch($query);

        if ($this->stageId > 0) {
            $query->where('leads.lead_stage_id', $this->stageId);
        }

        if (!$this->force) {
            $query->where(function (Builder $subQuery) {
                $subQuery->whereNull('lead_map_points.id')
                    ->orWhereNull('lead_map_points.lat')
                    ->orWhereNull('lead_map_points.lng')
                    ->orWhereIn('lead_map_points.geocode_status', [
                        'missing_location',
                        'zero_results',
                        'ambiguous_city',
                        'unknown_state',
                        'request_failed',
                        'exception',
                        'invalid_response',
                    ]);
            });
        }

        return $query->orderBy('leads.id');
    }

    private function applyScope(Builder $query): void
    {
        if ($this->scope === 'duplicates') {
            $query->whereNotNull('leads.duplicate_of_lead_id');
            return;
        }

        if ($this->scope === 'active') {
            $query->whereNull('leads.duplicate_of_lead_id');
        }
    }

    private function applySearch(Builder $query): void
    {
        if ($this->q === '') {
            return;
        }

        $q = $this->q;

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
}
