<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\JobAvailable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobAvailableController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $upcomingOnly = $request->boolean('upcoming_only');
        $perPage = max(1, min(200, (int) $request->query('per_page', 20)));

        $rows = $this->applyRosterCounts(
            JobAvailable::query()
                ->when($q !== '', function (Builder $query) use ($q) {
                    $query->where(function (Builder $sub) use ($q) {
                        $sub->where('job_number', 'like', "%{$q}%")
                            ->orWhere('title', 'like', "%{$q}%")
                            ->orWhere('origin_city', 'like', "%{$q}%")
                            ->orWhere('origin_state', 'like', "%{$q}%")
                            ->orWhere('destination_city', 'like', "%{$q}%")
                            ->orWhere('destination_state', 'like', "%{$q}%")
                            ->orWhere('equipment_type', 'like', "%{$q}%")
                            ->orWhere('status', 'like', "%{$q}%")
                            ->orWhere('rate_description', 'like', "%{$q}%");
                    });
                })
                ->when($status !== '', function (Builder $query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($upcomingOnly, function (Builder $query) {
                    $today = Carbon::today()->toDateString();
                    $end = Carbon::today()->addDays(30)->toDateString();

                    $query->whereNotNull('job_start_date')
                        ->whereBetween('job_start_date', [$today, $end]);
                })
        )
            ->orderByRaw('job_start_date is null')
            ->orderBy('job_start_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        $rows->getCollection()->transform(fn (JobAvailable $job) => $this->presentJob($job));

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $this->validateJob($request);
        $data['created_by_admin_id'] = $request->user()->id;

        $row = DB::transaction(function () use ($data) {
            $job = JobAvailable::create($data);
            $this->syncGeneratedRosterRows($job);

            return $job;
        });

        $row = $this->applyRosterCounts(JobAvailable::query()->whereKey($row->id))->firstOrFail();

        return response()->json($this->presentJob($row), 201);
    }

    public function show(JobAvailable $jobs_available)
    {
        $job = $this->applyRosterCounts(JobAvailable::query()->whereKey($jobs_available->id))->firstOrFail();

        return response()->json($this->presentJob($job));
    }

    public function update(Request $request, JobAvailable $jobs_available)
    {
        $data = $this->validateJob($request);

        DB::transaction(function () use ($jobs_available, $data) {
            $jobs_available->update($data);
            $this->syncGeneratedRosterRows($jobs_available->fresh());
        });

        $job = $this->applyRosterCounts(JobAvailable::query()->whereKey($jobs_available->id))->firstOrFail();

        return response()->json($this->presentJob($job));
    }

    public function destroy(JobAvailable $jobs_available)
    {
        $jobs_available->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    private function validateJob(Request $request): array
    {
        $data = $request->validate([
            'job_number' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'origin_city' => ['nullable', 'string', 'max:255'],
            'origin_state' => ['nullable', 'string', 'max:255'],
            'destination_city' => ['nullable', 'string', 'max:255'],
            'destination_state' => ['nullable', 'string', 'max:255'],
            'equipment_type' => ['nullable', 'string', 'max:255'],
            'trailer_type' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric'],
            'rate' => ['nullable', 'numeric'],
            'rate_description' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'job_start_date' => ['nullable', 'date'],
            'primary_required' => ['nullable', 'integer', 'min:0'],
            'spare_allowed' => ['nullable', 'integer', 'min:0'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_company' => ['nullable', 'string', 'max:255'],
            'posted_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $data['primary_required'] = (int) ($data['primary_required'] ?? 0);
        $data['spare_allowed'] = (int) ($data['spare_allowed'] ?? 0);

        if (!array_key_exists('status', $data) || trim((string) $data['status']) === '') {
            $data['status'] = 'open';
        }

        if (
            (!array_key_exists('rate_description', $data) || trim((string) $data['rate_description']) === '')
            && array_key_exists('rate', $data)
            && $data['rate'] !== null
        ) {
            $data['rate_description'] = (string) $data['rate'];
        }

        return $data;
    }

    private function presentJob(JobAvailable $job): array
    {
        $primaryRequired = (int) ($job->primary_required ?? 0);
        $spareAllowed = (int) ($job->spare_allowed ?? 0);
        $primaryFilled = (int) ($job->primary_filled_count ?? 0);
        $spareFilled = (int) ($job->spare_filled_count ?? 0);
        $primaryOverfill = max(0, $primaryFilled - $primaryRequired);
        $spareOverfill = max(0, $spareFilled - $spareAllowed);
        $fillPercent = $primaryRequired > 0 ? round(($primaryFilled / $primaryRequired) * 100, 1) : 0.0;

        return [
            'id' => $job->id,
            'job_number' => $job->job_number,
            'title' => $job->title,
            'description' => $job->description,
            'origin_city' => $job->origin_city,
            'origin_state' => $job->origin_state,
            'destination_city' => $job->destination_city,
            'destination_state' => $job->destination_state,
            'equipment_type' => $job->equipment_type,
            'trailer_type' => $job->trailer_type,
            'weight' => $job->weight,
            'rate' => $job->rate,
            'rate_description' => $job->rate_description,
            'status' => $job->status,
            'job_start_date' => optional($job->job_start_date)->toDateString(),
            'primary_required' => $primaryRequired,
            'spare_allowed' => $spareAllowed,
            'customer_name' => $job->customer_name,
            'customer_company' => $job->customer_company,
            'posted_at' => optional($job->posted_at)->toDateString(),
            'expires_at' => optional($job->expires_at)->toDateString(),
            'roster_summary' => [
                'primary_required' => $primaryRequired,
                'primary_filled' => $primaryFilled,
                'spare_allowed' => $spareAllowed,
                'spare_filled' => $spareFilled,
                'primary_overfill' => $primaryOverfill,
                'spare_overfill' => $spareOverfill,
                'fill_percent' => $fillPercent,
                'display' => sprintf('%d / %d + %d', $primaryFilled, $primaryRequired, $spareFilled),
            ],
        ];
    }

    private function applyRosterCounts(Builder $query): Builder
    {
        return $query->withCount([
            'assignments as primary_filled_count' => function (Builder $builder) {
                $this->applyFilledScope($builder)->where('slot_type', 'primary');
            },
            'assignments as spare_filled_count' => function (Builder $builder) {
                $this->applyFilledScope($builder)->where('slot_type', 'spare');
            },
        ]);
    }

    private function applyFilledScope(Builder $query): Builder
    {
        return $query->where(function (Builder $builder) {
            $builder
                ->where(function (Builder $sub) {
                    $sub->whereNotNull('carrier_name')->where('carrier_name', '!=', '');
                })
                ->orWhere(function (Builder $sub) {
                    $sub->whereNotNull('driver_name')->where('driver_name', '!=', '');
                })
                ->orWhereNotNull('carrier_id')
                ->orWhereNotNull('lead_id');
        });
    }

    private function syncGeneratedRosterRows(JobAvailable $job): void
    {
        $this->normalizeSlotOrders($job, 'primary');
        $this->normalizeSlotOrders($job, 'spare');

        $this->syncSlotTypeRows($job, 'primary', (int) ($job->primary_required ?? 0));
        $this->syncSlotTypeRows($job, 'spare', (int) ($job->spare_allowed ?? 0));
    }

    private function syncSlotTypeRows(JobAvailable $job, string $slotType, int $requiredCount): void
    {
        $rows = $job->assignments()
            ->where('slot_type', $slotType)
            ->orderBy('slot_order')
            ->orderBy('id')
            ->get();

        $currentCount = $rows->count();

        if ($currentCount < $requiredCount) {
            for ($i = $currentCount + 1; $i <= $requiredCount; $i++) {
                $row = new JobAssignment();
                $row->job_available_id = $job->id;
                $row->slot_type = $slotType;
                $row->slot_order = $i;
                $row->source_type = 'manual';
                $row->carrier_id = null;
                $row->lead_id = null;
                $row->internal_poc_user_id = null;
                $row->carrier_name = null;
                $row->driver_name = null;
                $row->status = $this->defaultStatusForSlot($slotType);
                $row->truck_number = null;
                $row->trailer_owner_type = 'carrier';
                $row->trailer_id = null;
                $row->expected_start_date = null;
                $row->readiness_checked_at = null;
                $row->notes = null;
                $row->save();
            }
        }

        if ($currentCount > $requiredCount) {
            $rows
                ->reverse()
                ->each(function (JobAssignment $row) use ($requiredCount) {
                    if ((int) $row->slot_order <= $requiredCount) {
                        return false;
                    }

                    if ($this->isFilledAssignment($row)) {
                        return null;
                    }

                    $row->delete();

                    return null;
                });
        }

        $this->normalizeSlotOrders($job, $slotType);
    }

    private function normalizeSlotOrders(JobAvailable $job, string $slotType): void
    {
        $rows = $job->assignments()
            ->where('slot_type', $slotType)
            ->orderByRaw('case when slot_order <= 0 then 1 else 0 end')
            ->orderBy('slot_order')
            ->orderBy('id')
            ->get();

        $position = 1;

        foreach ($rows as $row) {
            if ((int) $row->slot_order !== $position) {
                $row->slot_order = $position;
                $row->save();
            }

            $position++;
        }
    }

    private function isFilledAssignment(JobAssignment $assignment): bool
    {
        if ($assignment->carrier_id || $assignment->lead_id) {
            return true;
        }

        return trim((string) $assignment->carrier_name) !== '' || trim((string) $assignment->driver_name) !== '';
    }

    private function defaultStatusForSlot(string $slotType): string
    {
        return $slotType === 'spare' ? 'open_alternate' : 'open';
    }
}
