<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobAssignment;
use App\Models\JobAvailable;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobAssignmentController extends Controller
{
    public function index(Request $request, JobAvailable $jobs_available)
    {
        $job = DB::transaction(function () use ($jobs_available) {
            $job = JobAvailable::query()->findOrFail($jobs_available->id);
            $this->syncGeneratedRosterRows($job);

            return JobAvailable::query()->findOrFail($job->id);
        });

        return response()->json([
            'rows' => $this->presentRows($job),
            'summary' => $this->buildSummary($job),
        ]);
    }

    public function options(Request $request, JobAvailable $jobs_available)
    {
        $search = $this->normalizeText($request->input('q'));

        $assignedLeadIds = JobAssignment::query()
            ->where('job_available_id', $jobs_available->id)
            ->whereNotNull('lead_id')
            ->pluck('lead_id')
            ->filter()
            ->map(fn ($value) => (int) $value)
            ->values();

        $leadQuery = Lead::query()
            ->with(['carrier:id,company_name,contact_name'])
            ->orderByDesc('id');

        if ($search) {
            $leadQuery->where(function (Builder $query) use ($search) {
                $query
                    ->where('full_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%')
                    ->orWhere('state', 'like', '%' . $search . '%');
            });
        }

        $limit = $search ? 100 : 60;

        $leads = $leadQuery
            ->limit($limit)
            ->get(['id', 'full_name', 'email', 'phone', 'linked_carrier_id']);

        if ($assignedLeadIds->isNotEmpty()) {
            $assignedLeads = Lead::query()
                ->with(['carrier:id,company_name,contact_name'])
                ->whereIn('id', $assignedLeadIds->all())
                ->get(['id', 'full_name', 'email', 'phone', 'linked_carrier_id']);

            $leads = $assignedLeads->concat($leads)->unique('id')->values();
        }

        return response()->json([
            'pocs' => [],
            'carriers' => [],
            'leads' => $leads
                ->map(fn (Lead $lead) => $this->presentLeadOption($lead))
                ->values()
                ->all(),
        ]);
    }

    public function store(Request $request, JobAvailable $jobs_available)
    {
        $data = $this->validateAssignment($request, null, (int) $jobs_available->id);
        $row = null;

        DB::transaction(function () use ($jobs_available, $data, &$row) {
            $row = new JobAssignment();
            $row->job_available_id = $jobs_available->id;
            $row->slot_type = $data['slot_type'];
            $row->slot_order = $this->nextSlotOrder((int) $jobs_available->id, $data['slot_type']);
            $this->applyManualData($row, $data);
            $row->save();

            $this->syncGeneratedRosterRows(JobAvailable::query()->findOrFail($jobs_available->id));
        });

        return response()->json([
            'ok' => true,
            'id' => $row?->id,
        ], 201);
    }

    public function update(Request $request, JobAssignment $job_assignment)
    {
        $data = $this->validateAssignment($request, $job_assignment, (int) $job_assignment->job_available_id);

        DB::transaction(function () use ($job_assignment, $data) {
            $originalSlotType = (string) $job_assignment->slot_type;
            $newSlotType = (string) $data['slot_type'];

            if ($newSlotType !== $originalSlotType) {
                $job_assignment->slot_type = $newSlotType;
                $job_assignment->slot_order = $this->nextSlotOrder((int) $job_assignment->job_available_id, $newSlotType);
            }

            $this->applyManualData($job_assignment, $data);
            $job_assignment->save();

            $this->syncGeneratedRosterRows(JobAvailable::query()->findOrFail($job_assignment->job_available_id));
        });

        return response()->json([
            'ok' => true,
        ]);
    }

    public function destroy(JobAssignment $job_assignment)
    {
        DB::transaction(function () use ($job_assignment) {
            $jobAvailableId = (int) $job_assignment->job_available_id;
            $job_assignment->delete();
            $this->syncGeneratedRosterRows(JobAvailable::query()->findOrFail($jobAvailableId));
        });

        return response()->json([
            'ok' => true,
        ]);
    }

    public function markReady(JobAssignment $job_assignment)
    {
        $job_assignment->status = 'ready';
        $job_assignment->readiness_checked_at = now();
        $job_assignment->save();

        return response()->json([
            'ok' => true,
        ]);
    }

    private function validateAssignment(Request $request, ?JobAssignment $current = null, ?int $jobAvailableId = null): array
    {
        $data = $request->validate([
            'slot_type' => ['required', 'in:primary,spare'],
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'carrier_name' => ['nullable', 'string', 'max:25'],
            'driver_name' => ['nullable', 'string', 'max:25'],
            'status' => ['nullable', 'in:open,ready,pending_paperwork,open_alternate'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['lead_id'] = isset($data['lead_id']) && (int) $data['lead_id'] > 0
            ? (int) $data['lead_id']
            : null;
        $data['carrier_name'] = $this->normalizeText($data['carrier_name'] ?? null);
        $data['driver_name'] = $this->normalizeText($data['driver_name'] ?? null);
        $data['status'] = $this->normalizeStatus(
            $data['status'] ?? null,
            $data['slot_type'],
        );
        $data['notes'] = $this->normalizeText($data['notes'] ?? null);

        $this->assertUniqueLeadWithinJob(
            $jobAvailableId,
            $data['lead_id'] ?? null,
            $current?->id
        );

        $this->assertUniqueDriverNameWithinJob(
            $jobAvailableId,
            $data['driver_name'] ?? null,
            $data['lead_id'] ?? null,
            $current?->id
        );

        return $data;
    }

    private function assertUniqueLeadWithinJob(?int $jobAvailableId, ?int $leadId, ?int $ignoreId = null): void
    {
        if (!$jobAvailableId || !$leadId) {
            return;
        }

        $duplicateExists = JobAssignment::query()
            ->where('job_available_id', $jobAvailableId)
            ->where('lead_id', $leadId)
            ->when($ignoreId, function (Builder $query) use ($ignoreId) {
                $query->whereKeyNot($ignoreId);
            })
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'lead_id' => ['This lead is already used in another roster row for this job.'],
            ]);
        }
    }

    private function assertUniqueDriverNameWithinJob(?int $jobAvailableId, ?string $driverName, ?int $leadId = null, ?int $ignoreId = null): void
    {
        $normalized = $this->normalizeDriverIdentity($driverName, $leadId);

        if (!$jobAvailableId || $normalized === '') {
            return;
        }

        $duplicateExists = JobAssignment::query()
            ->with(['lead:id,full_name'])
            ->where('job_available_id', $jobAvailableId)
            ->when($ignoreId, function (Builder $query) use ($ignoreId) {
                $query->whereKeyNot($ignoreId);
            })
            ->get(['id', 'lead_id', 'driver_name'])
            ->contains(function (JobAssignment $row) use ($normalized) {
                return $this->normalizeDriverIdentity($row->driver_name, $row->lead_id, $row->lead?->full_name) === $normalized;
            });

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'driver_name' => ['This driver is already used in another roster row for this job.'],
            ]);
        }
    }

    private function applyManualData(JobAssignment $row, array $data): void
    {
        $leadId = isset($data['lead_id']) ? (int) $data['lead_id'] : 0;

        $row->source_type = $leadId > 0 ? 'lead' : 'manual';
        $row->carrier_id = null;
        $row->lead_id = $leadId > 0 ? $leadId : null;
        $row->internal_poc_user_id = null;

        $row->carrier_name = $data['carrier_name'];
        $row->driver_name = $data['driver_name'];
        $row->status = $data['status'];
        $row->notes = $data['notes'] ?? null;

        if ($row->status === 'ready') {
            $row->readiness_checked_at = now();
        } elseif ($row->status !== 'pending_paperwork') {
            $row->readiness_checked_at = null;
        }
    }

    private function presentRows(JobAvailable $job): array
    {
        $job->load([
            'assignments' => function ($query) {
                $query
                    ->orderByRaw("case when slot_type = 'primary' then 0 else 1 end")
                    ->orderBy('slot_order')
                    ->orderBy('id');
            },
            'assignments.carrier:id,company_name,contact_name',
            'assignments.lead:id,full_name,email,phone,linked_carrier_id',
            'assignments.lead.carrier:id,company_name,contact_name',
        ]);

        $assignments = $job->assignments instanceof Collection
            ? $job->assignments
            : collect($job->assignments);

        $primaryRequired = (int) ($job->primary_required ?? 0);
        $spareAllowed = (int) ($job->spare_allowed ?? 0);

        $rows = [];
        $slotNumbers = [
            'primary' => 0,
            'spare' => 0,
        ];

        foreach ($assignments as $assignment) {
            $slotType = (string) $assignment->slot_type === 'spare' ? 'spare' : 'primary';
            $slotNumbers[$slotType]++;
            $slotNumber = $slotNumbers[$slotType];
            $isOverfill = $slotType === 'spare'
                ? $slotNumber > $spareAllowed
                : $slotNumber > $primaryRequired;

            $rows[] = $this->presentAssignment($assignment, $slotType, $slotNumber, $isOverfill);
        }

        return $rows;
    }

    private function presentAssignment(JobAssignment $assignment, string $slotType, int $slotNumber, bool $isOverfill): array
    {
        $carrierName = $this->resolveCarrierName($assignment);
        $driverName = $this->resolveDriverName($assignment);
        $status = $this->normalizeStatus($assignment->status, $slotType);
        $statusLabel = $this->statusLabel($status);

        if ($isOverfill) {
            $statusLabel .= ' · Overfill';
        }

        return [
            'id' => $assignment->id,
            'slot_type' => $slotType,
            'slot_order' => (int) $assignment->slot_order,
            'slot_number' => $slotNumber,
            'slot_label' => $slotType === 'spare' ? "On-Call {$slotNumber}" : "Position {$slotNumber}",
            'source_type' => $assignment->source_type,
            'lead_id' => $assignment->lead_id ? (int) $assignment->lead_id : null,
            'lead_label' => $assignment->lead ? $this->presentLeadOptionLabel($assignment->lead) : null,
            'carrier_name' => $carrierName !== '' ? $carrierName : null,
            'driver_name' => $driverName !== '' ? $driverName : null,
            'status' => $status,
            'status_key' => $status,
            'status_label' => $statusLabel,
            'is_filled' => $this->isFilledAssignment($assignment),
            'is_overfill' => $isOverfill,
            'notes' => $assignment->notes,
            'readiness_checked_at' => optional($assignment->readiness_checked_at)->toIso8601String(),
        ];
    }

    private function buildSummary(JobAvailable $job): array
    {
        $primaryRequired = (int) ($job->primary_required ?? 0);
        $spareAllowed = (int) ($job->spare_allowed ?? 0);

        $primaryFilled = $job->assignments()
            ->where('slot_type', 'primary')
            ->where(function (Builder $query) {
                $this->applyFilledScope($query);
            })
            ->count();

        $spareFilled = $job->assignments()
            ->where('slot_type', 'spare')
            ->where(function (Builder $query) {
                $this->applyFilledScope($query);
            })
            ->count();

        return [
            'primary_required' => $primaryRequired,
            'primary_filled' => $primaryFilled,
            'spare_allowed' => $spareAllowed,
            'spare_filled' => $spareFilled,
            'primary_overfill' => max(0, $primaryFilled - $primaryRequired),
            'spare_overfill' => max(0, $spareFilled - $spareAllowed),
            'fill_percent' => $primaryRequired > 0 ? round(($primaryFilled / $primaryRequired) * 100, 1) : 0.0,
            'display' => sprintf('%d / %d + %d', $primaryFilled, $primaryRequired, $spareFilled),
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

    private function nextSlotOrder(int $jobAvailableId, string $slotType): int
    {
        return (int) JobAssignment::query()
                ->where('job_available_id', $jobAvailableId)
                ->where('slot_type', $slotType)
                ->max('slot_order') + 1;
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

    private function resolveCarrierName(JobAssignment $assignment): string
    {
        return trim((string) (
        $assignment->carrier_name
            ?: $assignment->carrier?->company_name
            ?: $assignment->carrier?->contact_name
                ?: $assignment->lead?->carrier?->company_name
                    ?: $assignment->lead?->carrier?->contact_name
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

    private function normalizeDriverIdentity(?string $driverName, ?int $leadId = null, ?string $resolvedLeadName = null): string
    {
        $driverName = $this->normalizeDriverName($driverName);

        if ($driverName !== '') {
            return $driverName;
        }

        if (!$leadId) {
            return '';
        }

        $leadName = $resolvedLeadName;

        if ($leadName === null) {
            $leadName = Lead::query()
                ->whereKey($leadId)
                ->value('full_name');
        }

        return $this->normalizeDriverName($leadName);
    }

    private function presentLeadOption(Lead $lead): array
    {
        return [
            'id' => (int) $lead->id,
            'full_name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'carrier_name' => $this->normalizeText(
                $lead->carrier?->company_name
                    ?: $lead->carrier?->contact_name
                    ?: null
            ),
            'label' => $this->presentLeadOptionLabel($lead),
        ];
    }

    private function presentLeadOptionLabel(Lead $lead): string
    {
        $parts = array_values(array_filter([
            $this->normalizeText($lead->full_name),
            $this->normalizeText($lead->phone),
            $this->normalizeText($lead->email),
        ]));

        $label = implode(' | ', $parts);

        if ($label !== '') {
            return $label;
        }

        return 'Lead #' . $lead->id;
    }

    private function normalizeText(?string $value): ?string
    {
        $value = trim((string) $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : $value;
    }

    private function normalizeDriverName(?string $value): string
    {
        return strtolower((string) ($this->normalizeText($value) ?? ''));
    }

    private function defaultStatusForSlot(string $slotType): string
    {
        return $slotType === 'spare' ? 'open_alternate' : 'open';
    }

    private function normalizeStatus(?string $value, string $slotType): string
    {
        $value = trim((string) $value);
        $value = $value === '' ? $this->defaultStatusForSlot($slotType) : $value;

        if ($slotType === 'primary' && $value === 'open_alternate') {
            return 'open';
        }

        if ($slotType === 'spare' && $value === 'open') {
            return 'open_alternate';
        }

        return in_array($value, ['open', 'ready', 'pending_paperwork', 'open_alternate'], true)
            ? $value
            : $this->defaultStatusForSlot($slotType);
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ready' => 'Ready',
            'pending_paperwork' => 'Pending paperwork',
            'open_alternate' => 'Open on-call',
            default => 'Open',
        };
    }
}
