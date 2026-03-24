<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\JobAssignment;
use App\Models\JobAvailable;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        $windowEnd = Carbon::today()->addDays(30)->toDateString();

        $upcomingJobs = JobAvailable::query()
            ->withCount([
                'assignments as primary_filled_count' => function (Builder $query) {
                    $this->applyFilledScope($query)->where('slot_type', 'primary');
                },
                'assignments as spare_filled_count' => function (Builder $query) {
                    $this->applyFilledScope($query)->where('slot_type', 'spare');
                },
            ])
            ->with([
                'assignments' => function ($query) {
                    $query
                        ->orderByRaw("case when slot_type = 'primary' then 0 else 1 end")
                        ->orderBy('slot_order')
                        ->orderBy('id');
                },
            ])
            ->where('status', 'open')
            ->whereNotNull('job_start_date')
            ->whereDate('job_start_date', '<=', $windowEnd)
            ->orderBy('job_start_date')
            ->get()
            ->map(function (JobAvailable $job) {
                $primaryRequired = (int) ($job->primary_required ?? 0);
                $spareAllowed = (int) ($job->spare_allowed ?? 0);
                $primaryFilled = (int) ($job->primary_filled_count ?? 0);
                $spareFilled = (int) ($job->spare_filled_count ?? 0);

                $carriers = $job->assignments
                    ->map(function (JobAssignment $assignment) {
                        $carrierName = $this->resolveCarrierName($assignment);

                        return $carrierName !== '' ? $carrierName : null;
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $job->id,
                    'job_number' => $job->job_number,
                    'title' => $job->title,
                    'job_start_date' => optional($job->job_start_date)->toDateString(),
                    'status' => $job->status,
                    'roster_summary' => [
                        'primary_required' => $primaryRequired,
                        'primary_filled' => $primaryFilled,
                        'spare_allowed' => $spareAllowed,
                        'spare_filled' => $spareFilled,
                        'fill_percent' => $primaryRequired > 0 ? round(($primaryFilled / $primaryRequired) * 100, 1) : 0.0,
                        'display' => sprintf('%d / %d + %d', $primaryFilled, $primaryRequired, $spareFilled),
                    ],
                    'roster_carriers' => $carriers,
                    'roster_slots' => $this->buildRosterSlots($job),
                ];
            })
            ->values();

        return response()->json([
            'cards' => [
                'admin_users' => User::admins()->count(),
                'leads_total' => Lead::count(),
                'leads_new' => Lead::where('lead_status', 'new')->count(),
                'carriers_total' => Carrier::count(),
                'jobs_open' => JobAvailable::where('status', 'open')->count(),
            ],
            'upcoming_jobs' => $upcomingJobs,
        ]);
    }

    private function buildRosterSlots(JobAvailable $job): array
    {
        $assignments = $job->assignments instanceof Collection
            ? $job->assignments
            : collect($job->assignments);

        $primaryAssignments = $assignments
            ->where('slot_type', 'primary')
            ->values();

        $spareAssignments = $assignments
            ->where('slot_type', 'spare')
            ->values();

        $primaryRequired = (int) ($job->primary_required ?? 0);
        $spareAllowed = (int) ($job->spare_allowed ?? 0);

        $primarySlotCount = max($primaryRequired, $primaryAssignments->count());
        $spareSlotCount = max($spareAllowed, $spareAssignments->count());

        $slots = collect();

        for ($i = 0; $i < $primarySlotCount; $i++) {
            $assignment = $primaryAssignments->get($i);
            $slotNumber = $i + 1;

            $slots->push(
                $this->presentRosterSlot(
                    $assignment,
                    'primary',
                    $slotNumber,
                    $slotNumber > $primaryRequired
                )
            );
        }

        for ($i = 0; $i < $spareSlotCount; $i++) {
            $assignment = $spareAssignments->get($i);
            $slotNumber = $i + 1;

            $slots->push(
                $this->presentRosterSlot(
                    $assignment,
                    'spare',
                    $slotNumber,
                    $slotNumber > $spareAllowed
                )
            );
        }

        return $slots->values()->all();
    }

    private function presentRosterSlot(?JobAssignment $assignment, string $slotType, int $slotNumber, bool $isOverfill): array
    {
        $slotLabel = $slotType === 'spare'
            ? sprintf('On-Call %d', $slotNumber)
            : sprintf('Position %d', $slotNumber);

        if (!$assignment || !$this->isFilledAssignment($assignment)) {
            $statusKey = $slotType === 'spare' ? 'open_alternate' : 'open';
            $statusLabel = $slotType === 'spare' ? 'Open on-call' : 'Open';

            if ($isOverfill) {
                $statusLabel .= ' · Overfill';
            }

            return [
                'slot_type' => $slotType,
                'slot_number' => $slotNumber,
                'slot_label' => $slotLabel,
                'display_name' => $slotType === 'spare'
                    ? sprintf('Open on-call %d', $slotNumber)
                    : sprintf('Open position %d', $slotNumber),
                'carrier_name' => null,
                'driver_name' => null,
                'status_key' => $statusKey,
                'status_label' => $statusLabel,
                'is_filled' => false,
                'is_overfill' => $isOverfill,
                'source_name' => null,
                'readiness_checked_at' => null,
            ];
        }

        $carrierName = $this->resolveCarrierName($assignment);
        $driverName = $this->resolveDriverName($assignment);
        $statusKey = $this->normalizeStatusKey($assignment->status, $slotType);
        $statusLabel = $this->statusLabel($statusKey);

        if ($isOverfill) {
            $statusLabel .= ' · Overfill';
        }

        $displayName = $carrierName !== ''
            ? $carrierName
            : ($slotType === 'spare' ? 'Assigned on-call' : 'Assigned position');

        return [
            'slot_type' => $slotType,
            'slot_number' => $slotNumber,
            'slot_label' => $slotLabel,
            'display_name' => $displayName,
            'carrier_name' => $carrierName !== '' ? $carrierName : null,
            'driver_name' => $driverName !== '' ? $driverName : null,
            'status_key' => $statusKey,
            'status_label' => $statusLabel,
            'is_filled' => true,
            'is_overfill' => $isOverfill,
            'source_name' => $displayName,
            'readiness_checked_at' => optional($assignment->readiness_checked_at)->toIso8601String(),
        ];
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

    private function isFilledAssignment(JobAssignment $assignment): bool
    {
        if ($assignment->carrier_id || $assignment->lead_id) {
            return true;
        }

        return trim((string) $assignment->carrier_name) !== '' || trim((string) $assignment->driver_name) !== '';
    }

    private function resolveCarrierName(JobAssignment $assignment): string
    {
        return trim((string) (
        $assignment->carrier_name
            ?: $assignment->carrier?->company_name
            ?: $assignment->carrier?->contact_name
                ?: ''
        ));
    }

    private function resolveDriverName(JobAssignment $assignment): string
    {
        return trim((string) (
        $assignment->driver_name
            ?: $assignment->lead?->full_name
            ?: ''
        ));
    }

    private function normalizeStatusKey(?string $value, string $slotType): string
    {
        $value = trim((string) $value);

        if ($value === 'ready') {
            return 'ready';
        }

        if ($value === 'pending_paperwork') {
            return 'pending_paperwork';
        }

        if ($slotType === 'spare') {
            return 'open_alternate';
        }

        return 'open';
    }

    private function statusLabel(string $value): string
    {
        return match ($value) {
            'ready' => 'Ready',
            'pending_paperwork' => 'Pending paperwork',
            'open_alternate' => 'Open on-call',
            default => 'Open',
        };
    }
}
