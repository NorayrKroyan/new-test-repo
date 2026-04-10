<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrier;
use App\Models\Lead;
use App\Models\Stage;
use App\Reports\LeadFunnelReport;
use App\Services\DialpadCallHistoryService;
use App\Services\DialpadContactService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class LeadController extends Controller
{
    public function __construct(
        protected DialpadContactService $dialpadContacts,
        protected DialpadCallHistoryService $dialpadCallHistory
    ) {
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $scope = $this->normalizeScope($request->query('scope'));

        $query = Lead::query()
            ->select($this->leadListColumns())
            ->with([
                'duplicateMaster:id,full_name,email,phone,lead_status',
                'stage:id,stage_name,stage_group,stage_order',
            ])
            ->withCount('duplicates');

        $this->applyScope($query, $scope);
        $this->applyAdNameFilter($query, $request->query('ad_name'));
        $this->applySearch($query, $q);
        $this->applyStageFilter($query, $request->query('stage_id'));

        $rows = $query
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $rows,
            'meta' => [
                'scope' => $scope,
                'counts' => $this->buildCounts(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateLead($request);
        $data = $this->normalizeDuplicatePayload($data);

        $row = Lead::create($data);
        $dialpadSync = $this->syncLeadContactAfterSave($row, true);

        return response()->json($this->buildLeadResponse($row, $dialpadSync), 201);
    }

    public function show(Lead $lead)
    {
        return response()->json($this->hydrateLead($lead));
    }

    public function syncContact(Lead $lead)
    {
        try {
            $dialpadSync = $this->syncLeadContactNow($lead);
        } catch (Throwable $e) {
            throw ValidationException::withMessages([
                'dialpad' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'ok' => true,
            'lead' => $this->hydrateLead($lead),
            'dialpad_sync' => $dialpadSync,
        ]);
    }

    public function callHistory(Request $request, Lead $lead)
    {
        $syncSummary = null;
        $syncError = null;

        if ($request->boolean('sync')) {
            try {
                $syncSummary = $this->dialpadCallHistory->syncLead($lead);
            } catch (Throwable $e) {
                $syncError = $e->getMessage();
            }
        }

        $rows = $this->loadLeadCallHistoryRows(
            $lead->id,
            $request->boolean('include_demo')
        );

        return response()->json([
            'data' => $rows,
            'meta' => [
                'lead_id' => $lead->id,
                'table' => 'lead_call_histories',
                'include_demo' => $request->boolean('include_demo'),
                'sync' => $syncSummary,
                'sync_error' => $syncError,
            ],
        ]);
    }

    public function smsHistory(Lead $lead)
    {
        $rows = $this->loadLeadSmsHistoryRows($lead->id);

        return response()->json([
            'data' => $rows,
            'meta' => [
                'lead_id' => $lead->id,
                'table' => 'lead_sms_histories',
            ],
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $this->validateLead($request, $lead);
        $data = $this->normalizeDuplicatePayload($data, $lead);
        $shouldSyncDialpad = $this->leadContactFieldsChanged($lead, $data);

        $lead->update($data);
        $dialpadSync = $this->syncLeadContactAfterSave($lead, $shouldSyncDialpad);

        return response()->json($this->buildLeadResponse($lead, $dialpadSync));
    }

    public function destroy(Lead $lead)
    {
        if ($lead->duplicates()->exists()) {
            throw ValidationException::withMessages([
                'lead' => 'This lead is the master for duplicate leads. Reassign or unmark those duplicates before deleting it.',
            ]);
        }

        $lead->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function convertToCarrier(Lead $lead)
    {
        if ($lead->duplicate_of_lead_id) {
            throw ValidationException::withMessages([
                'lead' => 'Duplicate leads cannot be converted. Convert the master lead instead.',
            ]);
        }

        if ($lead->linked_carrier_id || $lead->lead_status === 'converted_to_carrier') {
            throw ValidationException::withMessages([
                'lead' => 'This lead has already been converted to a carrier.',
            ]);
        }

        $carrier = DB::transaction(function () use ($lead) {
            $carrier = Carrier::create([
                'company_name' => $lead->company_name,
                'contact_name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'city' => $lead->city,
                'state' => $lead->state,
                'usdot' => $lead->usdot,
                'carrier_class' => $lead->carrier_class,
                'truck_count' => $lead->truck_count,
                'trailer_count' => $lead->trailer_count,
                'insurance_status' => $lead->insurance_answer === 'yes' ? 'active' : 'inactive',
                'status' => 'pending_review',
                'notes' => 'Converted from lead #' . $lead->id,
            ]);

            $lead->update([
                'linked_carrier_id' => $carrier->id,
                'lead_status' => 'converted_to_carrier',
            ]);

            $lead->delete();

            return $carrier;
        });

        return response()->json([
            'ok' => true,
            'carrier' => $carrier,
            'lead_id' => $lead->id,
            'lead_removed_from_leads' => true,
        ]);
    }

    public function autoDedup(Request $request)
    {
        $matchBy = $this->normalizeMatchBy($request->input('match_by'));
        $activeLeads = Lead::query()
            ->whereNull('duplicate_of_lead_id')
            ->get();

        $changes = collect();

        if (in_array($matchBy, ['any', 'phone'], true)) {
            $phoneChanges = $this->dedupeByBasis($activeLeads, 'phone');
            $changes = $changes->merge($phoneChanges);
            $activeLeads = $this->filterOutChangedLeads($activeLeads, $phoneChanges);
        }

        if (in_array($matchBy, ['any', 'email'], true)) {
            $emailChanges = $this->dedupeByBasis($activeLeads, 'email');
            $changes = $changes->merge($emailChanges);
        }

        return response()->json([
            'ok' => true,
            'summary' => [
                'match_by' => $matchBy,
                'duplicate_groups' => $changes->pluck('master_lead_id')->unique()->count(),
                'duplicates_marked' => $changes->count(),
            ],
            'data' => $changes->values(),
        ]);
    }

    public function markDuplicate(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'master_lead_id' => [
                'required',
                'integer',
                Rule::exists('leads', 'id')->whereNull('deleted_at'),
            ],
        ]);

        $master = Lead::query()->findOrFail($data['master_lead_id']);

        if ((int) $master->id === (int) $lead->id) {
            throw ValidationException::withMessages([
                'master_lead_id' => 'A lead cannot be marked as a duplicate of itself.',
            ]);
        }

        if ($master->duplicate_of_lead_id) {
            $master = $this->resolveDuplicateRoot($master);
        }

        if ($lead->duplicates()->exists()) {
            throw ValidationException::withMessages([
                'lead' => 'This lead is currently the master for duplicate leads. Unmark or reassign those duplicates first.',
            ]);
        }

        $lead->update([
            'duplicate_of_lead_id' => $master->id,
            'duplicate_basis' => 'manual',
            'lead_status' => 'duplicate',
        ]);

        return response()->json([
            'ok' => true,
            'lead' => $this->hydrateLead($lead),
        ]);
    }

    public function unmarkDuplicate(Lead $lead)
    {
        if (!$lead->duplicate_of_lead_id) {
            throw ValidationException::withMessages([
                'lead' => 'This lead is not marked as a duplicate.',
            ]);
        }

        $lead->update([
            'duplicate_of_lead_id' => null,
            'duplicate_basis' => null,
            'lead_status' => 'new',
        ]);

        return response()->json([
            'ok' => true,
            'lead' => $this->hydrateLead($lead),
        ]);
    }

    public function mergePreview(Lead $lead)
    {
        $group = $this->resolveDuplicateGroup($lead);

        return response()->json([
            'ok' => true,
            'data' => $this->buildMergePreview($group),
        ]);
    }

    public function merge(Request $request, Lead $lead)
    {
        $group = $this->resolveDuplicateGroup($lead);
        $groupIds = $group->pluck('id')->map(fn ($id) => (int) $id)->all();

        $request->validate([
            'survivor_lead_id' => ['required', 'integer', Rule::in($groupIds)],
            'selections' => ['required', 'array'],
        ]);

        $fieldDefinitions = $this->mergeFieldDefinitions();

        foreach ($fieldDefinitions as $field) {
            $sourceLeadId = (int) data_get($request->input('selections'), $field['key'], 0);

            if (!in_array($sourceLeadId, $groupIds, true)) {
                throw ValidationException::withMessages([
                    'selections.' . $field['key'] => 'Every merge field must be assigned to a lead from the same duplicate group.',
                ]);
            }
        }

        $survivor = DB::transaction(function () use ($request, $lead, $fieldDefinitions) {
            $group = $this->resolveDuplicateGroup($lead)->keyBy('id');
            $survivor = $group->get((int) $request->integer('survivor_lead_id'));

            if (!$survivor) {
                throw ValidationException::withMessages([
                    'survivor_lead_id' => 'Invalid survivor lead selected.',
                ]);
            }

            $fieldSources = [];
            $survivorPayload = [];

            foreach ($fieldDefinitions as $field) {
                $fieldKey = $field['key'];
                $sourceLeadId = (int) data_get($request->input('selections'), $fieldKey);
                $sourceLead = $group->get($sourceLeadId);

                if (!$sourceLead) {
                    throw ValidationException::withMessages([
                        'selections.' . $fieldKey => 'Invalid source lead selected for field ' . $field['label'] . '.',
                    ]);
                }

                $fieldSources[$fieldKey] = $sourceLeadId;
                $survivorPayload[$fieldKey] = $sourceLead->{$fieldKey};
            }

            $survivorPayload['notes'] = $this->appendNotes(
                (string) ($survivorPayload['notes'] ?? ''),
                $this->buildMergeHistoryLog($group->values(), $survivor, $fieldSources)
            );

            $survivorPayload['lead_status'] = $this->normalizeSurvivorStatus($survivor->lead_status);
            $survivorPayload['duplicate_of_lead_id'] = null;
            $survivorPayload['duplicate_basis'] = null;
            $survivorPayload['merged_at'] = null;
            $survivorPayload['merged_by_user_id'] = null;
            $survivorPayload['merge_notes'] = null;

            $survivor->update($survivorPayload);

            $mergedByUserId = optional($request->user())->id;

            foreach ($group as $item) {
                if ((int) $item->id === (int) $survivor->id) {
                    continue;
                }

                $item->update([
                    'duplicate_of_lead_id' => $survivor->id,
                    'duplicate_basis' => 'merged',
                    'lead_status' => 'merged',
                    'merged_at' => now(),
                    'merged_by_user_id' => $mergedByUserId,
                    'merge_notes' => $this->buildMergedSourceSnapshot($item, $survivor, $fieldSources),
                ]);

                $item->delete();
            }

            return $survivor->fresh();
        });

        return response()->json([
            'ok' => true,
            'lead' => $this->hydrateLead($survivor),
        ]);
    }

    public function adNames(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $rows = Lead::query()
            ->whereNotNull('ad_name')
            ->where('ad_name', '!=', '')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('ad_name', 'like', "%{$q}%");
            })
            ->distinct()
            ->orderBy('ad_name')
            ->pluck('ad_name')
            ->values();

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function funnelSummary(Request $request)
    {
        return response()->json([
            'data' => $this->buildFunnelSummaryRows($request),
        ]);
    }

    public function funnelChart(Request $request)
    {
        $rows = $this->buildFunnelSummaryRows($request)
            ->sort(function ($a, $b) {
                $countCompare = ((int) $b->lead_count) <=> ((int) $a->lead_count);

                if ($countCompare !== 0) {
                    return $countCompare;
                }

                $stageOrderCompare = ((int) $a->stage_order) <=> ((int) $b->stage_order);

                if ($stageOrderCompare !== 0) {
                    return $stageOrderCompare;
                }

                return strcmp((string) $a->stage_name, (string) $b->stage_name);
            })
            ->values()
            ->map(function ($row) {
                $count = (int) $row->lead_count;

                return [
                    'label' => $row->stage_name . ': ' . $count,
                    'amount' => $count,
                ];
            })
            ->all();

        if (empty($rows)) {
            return response(<<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .empty {
            padding: 28px 16px;
            text-align: center;
            color: #64748b;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="empty">No funnel data.</div>
</body>
</html>
HTML, 200)->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $report = new LeadFunnelReport([
            'rows' => $rows,
        ]);

        $report->run();

        ob_start();
        $report->render();
        $html = ob_get_clean();

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function buildFunnelSummaryRows(Request $request): Collection
    {
        $q = trim((string) $request->query('q', ''));
        $scope = $this->normalizeScope($request->query('scope'));
        $stageId = (int) $request->query('stage_id', 0);
        $adName = trim((string) $request->query('ad_name', ''));

        if ($adName === '') {
            return collect();
        }

        $query = Lead::query()
            ->join('stages', 'stages.id', '=', 'leads.lead_stage_id')
            ->whereNull('leads.deleted_at')
            ->where('leads.ad_name', $adName)
            ->where('stages.stage_order', '<', 10);

        if ($scope === 'duplicates') {
            $query->whereNotNull('leads.duplicate_of_lead_id');
        } elseif ($scope === 'active') {
            $query->whereNull('leads.duplicate_of_lead_id');
        }

        if ($stageId > 0) {
            $query->where('leads.lead_stage_id', $stageId);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('leads.full_name', 'like', "%{$q}%")
                    ->orWhere('leads.email', 'like', "%{$q}%")
                    ->orWhere('leads.phone', 'like', "%{$q}%")
                    ->orWhere('leads.city', 'like', "%{$q}%")
                    ->orWhere('leads.state', 'like', "%{$q}%")
                    ->orWhere('leads.carrier_class', 'like', "%{$q}%")
                    ->orWhere('leads.platform', 'like', "%{$q}%")
                    ->orWhere('leads.ad_name', 'like', "%{$q}%")
                    ->orWhere('stages.stage_name', 'like', "%{$q}%")
                    ->orWhere('stages.stage_group', 'like', "%{$q}%");
            });
        }

        return $query
            ->groupBy('stages.id', 'stages.stage_name', 'stages.stage_group', 'stages.stage_order')
            ->orderBy('stages.stage_order')
            ->orderBy('stages.stage_name')
            ->get([
                'stages.id',
                'stages.stage_name',
                'stages.stage_group',
                'stages.stage_order',
                DB::raw('COUNT(leads.id) as lead_count'),
            ]);
    }

    private function buildLeadResponse(Lead $lead, ?array $dialpadSync = null): array
    {
        $payload = $this->hydrateLead($lead)->toArray();

        if ($dialpadSync) {
            $payload['dialpad_sync'] = $dialpadSync;
        }

        return $payload;
    }

    private function syncLeadContactAfterSave(Lead $lead, bool $shouldSync): array
    {
        if (!$shouldSync) {
            return [
                'status' => 'skipped',
                'message' => 'No lead contact changes detected.',
            ];
        }

        if (!$this->dialpadContacts->isConfigured()) {
            return [
                'status' => 'skipped',
                'message' => 'Dialpad integration is not configured.',
            ];
        }

        if (!$this->leadHasSyncableIdentity($lead)) {
            return [
                'status' => 'skipped',
                'message' => 'Lead must have at least one of name, phone, or email to sync.',
            ];
        }

        try {
            return $this->syncLeadContactNow($lead);
        } catch (Throwable $e) {
            return [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ];
        }
    }

    private function syncLeadContactNow(Lead $lead): array
    {
        $result = $this->dialpadContacts->syncLead($lead);

        return [
            'status' => 'synced',
            'message' => 'Dialpad contact synced.',
            'uid' => $result['uid'] ?? null,
            'contact_id' => $result['contact_id'] ?? null,
        ];
    }

    private function leadHasSyncableIdentity(Lead $lead): bool
    {
        return filled($lead->full_name) || filled($lead->email) || filled($lead->phone);
    }

    private function leadContactFieldsChanged(Lead $lead, array $data): bool
    {
        $incomingName = trim((string) ($data['full_name'] ?? $lead->full_name ?? ''));
        $currentName = trim((string) ($lead->full_name ?? ''));

        $incomingEmail = Lead::normalizeEmail($data['email'] ?? $lead->email);
        $currentEmail = Lead::normalizeEmail($lead->email);

        $incomingPhone = Lead::normalizePhone($data['phone'] ?? $lead->phone);
        $currentPhone = Lead::normalizePhone($lead->phone);

        return $incomingName !== $currentName
            || $incomingEmail !== $currentEmail
            || $incomingPhone !== $currentPhone;
    }

    private function hydrateLead(Lead $lead): Lead
    {
        return $lead->fresh([
            'duplicateMaster:id,full_name,email,phone,lead_status',
            'stage:id,stage_name,stage_group,stage_order',
        ])->loadCount('duplicates');
    }

    private function leadListColumns(): array
    {
        return [
            'id',
            'source_name',
            'ad_name',
            'platform',
            'source_created_at',
            'lead_date_choice',
            'insurance_answer',
            'full_name',
            'company_name',
            'email',
            'phone',
            'city',
            'state',
            'truck_type',
            'carrier_class',
            'usdot',
            'truck_count',
            'trailer_count',
            'lead_status',
            'lead_stage_id',
            'notes',
            'duplicate_of_lead_id',
            'duplicate_basis',
        ];
    }

    private function validateLead(Request $request, ?Lead $lead = null): array
    {
        $data = $request->validate([
            'source_name' => ['nullable', 'string', 'max:255'],
            'ad_name' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:255'],
            'source_created_at' => ['nullable', 'date'],
            'lead_date_choice' => ['nullable', 'string', 'max:255'],
            'insurance_answer' => ['nullable', 'string', 'max:255'],
            'full_name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'truck_type' => ['nullable', 'string', 'max:255'],
            'carrier_class' => ['nullable', 'string', 'max:255'],
            'usdot' => ['nullable', 'string', 'max:255'],
            'truck_count' => ['nullable', 'integer'],
            'trailer_count' => ['nullable', 'integer'],
            'lead_status' => ['nullable', 'string', 'max:255'],
            'lead_stage_id' => ['nullable', 'integer', Rule::exists('stages', 'id')],
            'notes' => ['nullable', 'string'],
            'duplicate_of_lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id')->whereNull('deleted_at'),
            ],
            'duplicate_basis' => ['nullable', 'string', 'max:50'],
            'sold_amount' => ['nullable', 'numeric'],
            'referral_fee' => ['nullable', 'numeric'],
            'sold_at' => ['nullable', 'date'],
        ]);

        if ($lead && array_key_exists('duplicate_of_lead_id', $data) && (int) $data['duplicate_of_lead_id'] === (int) $lead->id) {
            throw ValidationException::withMessages([
                'duplicate_of_lead_id' => 'A lead cannot be marked as a duplicate of itself.',
            ]);
        }

        $effectiveStageId = array_key_exists('lead_stage_id', $data)
            ? $data['lead_stage_id']
            : $lead?->lead_stage_id;

        $stageOrder = $effectiveStageId
            ? (int) (Stage::query()->whereKey($effectiveStageId)->value('stage_order') ?? 0)
            : 0;

        $data['funnel_enabled'] = $stageOrder < 10;

        return $data;
    }

    private function normalizeDuplicatePayload(array $data, ?Lead $lead = null): array
    {
        if (array_key_exists('duplicate_of_lead_id', $data) && $data['duplicate_of_lead_id']) {
            $master = Lead::query()->findOrFail($data['duplicate_of_lead_id']);

            if ($master->duplicate_of_lead_id) {
                $master = $this->resolveDuplicateRoot($master);
            }

            if ($lead && (int) $master->id === (int) $lead->id) {
                throw ValidationException::withMessages([
                    'duplicate_of_lead_id' => 'A lead cannot be marked as a duplicate of itself.',
                ]);
            }

            $data['duplicate_of_lead_id'] = $master->id;
            $data['lead_status'] = 'duplicate';
            $data['duplicate_basis'] = $data['duplicate_basis'] ?? 'manual';

            return $data;
        }

        if (array_key_exists('duplicate_of_lead_id', $data) && !$data['duplicate_of_lead_id']) {
            $data['duplicate_of_lead_id'] = null;
            $data['duplicate_basis'] = null;

            if (($data['lead_status'] ?? null) === 'duplicate') {
                $data['lead_status'] = 'new';
            }
        }

        if (($data['lead_status'] ?? null) === 'duplicate' && !($data['duplicate_of_lead_id'] ?? $lead?->duplicate_of_lead_id)) {
            throw ValidationException::withMessages([
                'lead_status' => 'Duplicate status requires a master lead.',
            ]);
        }

        return $data;
    }

    private function applySearch(Builder $query, string $q): void
    {
        if ($q === '') {
            return;
        }

        $query->where(function (Builder $sub) use ($q) {
            $sub->where('full_name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('company_name', 'like', "%{$q}%")
                ->orWhere('city', 'like', "%{$q}%")
                ->orWhere('state', 'like', "%{$q}%")
                ->orWhere('truck_type', 'like', "%{$q}%")
                ->orWhere('carrier_class', 'like', "%{$q}%")
                ->orWhere('platform', 'like', "%{$q}%")
                ->orWhere('ad_name', 'like', "%{$q}%")
                ->orWhere('lead_status', 'like', "%{$q}%")
                ->orWhere('duplicate_basis', 'like', "%{$q}%")
                ->orWhereHas('duplicateMaster', function (Builder $masterQuery) use ($q) {
                    $masterQuery->where('full_name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%");
                })
                ->orWhereHas('stage', function (Builder $stageQuery) use ($q) {
                    $stageQuery->where('stage_name', 'like', "%{$q}%")
                        ->orWhere('stage_group', 'like', "%{$q}%");
                });
        });
    }

    private function applyScope(Builder $query, string $scope): void
    {
        if ($scope === 'duplicates') {
            $query->whereNotNull('duplicate_of_lead_id');

            return;
        }

        if ($scope === 'active') {
            $query->whereNull('duplicate_of_lead_id');
        }
    }

    private function applyAdNameFilter(Builder $query, mixed $adName): void
    {
        $adName = trim((string) $adName);

        if ($adName !== '') {
            $query->where('ad_name', $adName);
        }
    }

    private function applyStageFilter(Builder $query, mixed $stageId): void
    {
        $stageId = (int) $stageId;

        if ($stageId > 0) {
            $query->where('lead_stage_id', $stageId);
        }
    }

    private function buildCounts(): array
    {
        $row = Lead::query()
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw('SUM(CASE WHEN duplicate_of_lead_id IS NULL THEN 1 ELSE 0 END) as active_count')
            ->selectRaw('SUM(CASE WHEN duplicate_of_lead_id IS NOT NULL THEN 1 ELSE 0 END) as duplicates_count')
            ->first();

        return [
            'all' => (int) ($row->all_count ?? 0),
            'active' => (int) ($row->active_count ?? 0),
            'duplicates' => (int) ($row->duplicates_count ?? 0),
        ];
    }

    private function normalizeScope(mixed $scope): string
    {
        $scope = strtolower(trim((string) $scope));

        return in_array($scope, ['all', 'active', 'duplicates'], true) ? $scope : 'active';
    }

    private function normalizeMatchBy(mixed $matchBy): string
    {
        $matchBy = strtolower(trim((string) $matchBy));

        return in_array($matchBy, ['any', 'phone', 'email'], true) ? $matchBy : 'any';
    }

    private function dedupeByBasis(EloquentCollection $leads, string $basis): Collection
    {
        $groups = $leads
            ->groupBy(function (Lead $lead) use ($basis) {
                return $basis === 'phone'
                    ? Lead::normalizePhone($lead->phone)
                    : Lead::normalizeEmail($lead->email);
            })
            ->filter(function (Collection $group, $key) {
                return $key !== null && $key !== '' && $group->count() > 1;
            });

        $changes = collect();

        DB::transaction(function () use ($groups, $basis, &$changes) {
            foreach ($groups as $group) {
                $sorted = $group->sort(function (Lead $a, Lead $b) {
                    return $this->compareLeadPriority($a, $b);
                })->values();

                $master = $sorted->first();

                foreach ($sorted->slice(1) as $duplicate) {
                    if ($duplicate->duplicates()->exists()) {
                        continue;
                    }

                    $duplicate->update([
                        'duplicate_of_lead_id' => $master->id,
                        'duplicate_basis' => $basis,
                        'lead_status' => 'duplicate',
                    ]);

                    $changes->push([
                        'lead_id' => $duplicate->id,
                        'lead_name' => $duplicate->full_name,
                        'master_lead_id' => $master->id,
                        'master_lead_name' => $master->full_name,
                        'basis' => $basis,
                    ]);
                }
            }
        });

        return $changes;
    }

    private function filterOutChangedLeads(EloquentCollection $leads, Collection $changes): EloquentCollection
    {
        if ($changes->isEmpty()) {
            return $leads;
        }

        $changedIds = $changes->pluck('lead_id')->map(fn ($id) => (int) $id)->all();

        return $leads->filter(function (Lead $lead) use ($changedIds) {
            return !in_array((int) $lead->id, $changedIds, true);
        })->values();
    }

    private function compareLeadPriority(Lead $a, Lead $b): int
    {
        $scoreA = $this->priorityScore($a);
        $scoreB = $this->priorityScore($b);

        if ($scoreA !== $scoreB) {
            return $scoreB <=> $scoreA;
        }

        return $a->id <=> $b->id;
    }

    private function priorityScore(Lead $lead): int
    {
        $score = 0;

        if ($lead->linked_carrier_id) {
            $score += 1000;
        }

        if ($lead->sold_at) {
            $score += 500;
        }

        if ($lead->assigned_admin_user_id) {
            $score += 200;
        }

        if ($lead->lead_status === 'converted_to_carrier') {
            $score += 250;
        }

        foreach (['full_name', 'email', 'phone', 'city', 'state', 'carrier_class', 'usdot', 'insurance_answer'] as $field) {
            if (filled($lead->{$field})) {
                $score += 10;
            }
        }

        foreach (['truck_count', 'trailer_count', 'sold_amount', 'referral_fee'] as $field) {
            if ($lead->{$field} !== null) {
                $score += 5;
            }
        }

        return $score;
    }

    private function resolveDuplicateRoot(Lead $lead): Lead
    {
        $current = $lead;

        while ($current->duplicate_of_lead_id) {
            $current = Lead::query()->findOrFail($current->duplicate_of_lead_id);
        }

        return $current;
    }

    private function resolveDuplicateGroup(Lead $lead): EloquentCollection
    {
        $root = $this->resolveDuplicateRoot($lead);

        $group = Lead::query()
            ->where(function (Builder $query) use ($root) {
                $query->where('id', $root->id)
                    ->orWhere('duplicate_of_lead_id', $root->id);
            })
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$root->id])
            ->orderBy('id')
            ->get();

        if ($group->count() < 2) {
            throw ValidationException::withMessages([
                'lead' => 'This lead does not currently have an active duplicate group to merge.',
            ]);
        }

        return $group;
    }

    private function buildMergePreview(EloquentCollection $group): array
    {
        $recommendedSurvivor = $group->sort(function (Lead $a, Lead $b) {
            return $this->compareLeadPriority($a, $b);
        })->values()->first();

        $fields = collect($this->mergeFieldDefinitions())->map(function (array $field) use ($group, $recommendedSurvivor) {
            $values = $group->map(function (Lead $lead) use ($field) {
                return [
                    'lead_id' => $lead->id,
                    'value' => $this->stringifyMergeValue($lead->{$field['key']}),
                    'filled' => $this->fieldFilled($lead->{$field['key']}),
                ];
            })->values();

            return [
                'key' => $field['key'],
                'label' => $field['label'],
                'recommended_source_lead_id' => $this->recommendedSourceLeadId($group, $recommendedSurvivor, $field['key']),
                'values' => $values,
            ];
        })->values();

        $conflictCount = $fields->filter(function (array $field) {
            $distinct = collect($field['values'])
                ->pluck('value')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => $value !== '' && $value !== '—')
                ->unique();

            return $distinct->count() > 1;
        })->count();

        return [
            'group_root_lead_id' => $recommendedSurvivor->duplicate_of_lead_id ? $this->resolveDuplicateRoot($recommendedSurvivor)->id : $recommendedSurvivor->id,
            'recommended_survivor_lead_id' => $recommendedSurvivor->id,
            'match_basis' => $group
                ->pluck('duplicate_basis')
                ->filter()
                ->unique()
                ->values()
                ->implode(', '),
            'conflict_count' => $conflictCount,
            'leads' => $group->map(function (Lead $lead) use ($recommendedSurvivor) {
                return [
                    'id' => $lead->id,
                    'full_name' => $lead->full_name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                    'city' => $lead->city,
                    'state' => $lead->state,
                    'lead_status' => $lead->lead_status,
                    'duplicate_basis' => $lead->duplicate_basis,
                    'duplicates_count' => $lead->duplicates()->count(),
                    'is_recommended_survivor' => (int) $lead->id === (int) $recommendedSurvivor->id,
                ];
            })->values(),
            'fields' => $fields,
        ];
    }

    private function mergeFieldDefinitions(): array
    {
        return [
            ['key' => 'full_name', 'label' => 'Full Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'phone', 'label' => 'Phone'],
            ['key' => 'company_name', 'label' => 'Company Name'],
            ['key' => 'city', 'label' => 'City'],
            ['key' => 'state', 'label' => 'State'],
            ['key' => 'truck_type', 'label' => 'Truck Type'],
            ['key' => 'carrier_class', 'label' => 'Carrier Class'],
            ['key' => 'usdot', 'label' => 'USDOT'],
            ['key' => 'truck_count', 'label' => 'Truck Count'],
            ['key' => 'trailer_count', 'label' => 'Trailer Count'],
            ['key' => 'insurance_answer', 'label' => 'Insurance'],
            ['key' => 'source_name', 'label' => 'Source Name'],
            ['key' => 'ad_name', 'label' => 'Ad Name'],
            ['key' => 'platform', 'label' => 'Platform'],
            ['key' => 'lead_date_choice', 'label' => 'Start Date Choice'],
            ['key' => 'notes', 'label' => 'Notes'],
        ];
    }

    private function recommendedSourceLeadId(EloquentCollection $group, Lead $recommendedSurvivor, string $fieldKey): int
    {
        $sorted = $group->sort(function (Lead $a, Lead $b) use ($fieldKey) {
            $scoreA = $this->fieldPriorityScore($a, $fieldKey);
            $scoreB = $this->fieldPriorityScore($b, $fieldKey);

            if ($scoreA !== $scoreB) {
                return $scoreB <=> $scoreA;
            }

            return $this->compareLeadPriority($a, $b);
        })->values();

        $candidate = $sorted->first();

        if (!$candidate || !$this->fieldFilled($candidate->{$fieldKey})) {
            return $recommendedSurvivor->id;
        }

        return $candidate->id;
    }

    private function fieldPriorityScore(Lead $lead, string $fieldKey): int
    {
        $score = $this->priorityScore($lead) * 100;

        if ($this->fieldFilled($lead->{$fieldKey})) {
            $score += 100000;
        }

        if ($fieldKey === 'email' && Lead::normalizeEmail($lead->email)) {
            $score += 1000;
        }

        if ($fieldKey === 'phone' && Lead::normalizePhone($lead->phone)) {
            $score += 1000;
        }

        if ($fieldKey === 'notes') {
            $score += strlen(trim((string) $lead->notes));
        }

        return $score;
    }

    private function fieldFilled(mixed $value): bool
    {
        if (is_numeric($value)) {
            return true;
        }

        return trim((string) $value) !== '';
    }

    private function stringifyMergeValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_string($value)) {
            $value = trim($value);

            return $value !== '' ? $value : '—';
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        return trim((string) $value) !== '' ? trim((string) $value) : '—';
    }

    private function normalizeSurvivorStatus(?string $status): string
    {
        $status = trim((string) $status);

        if ($status === '' || in_array($status, ['duplicate', 'merged'], true)) {
            return 'new';
        }

        return $status;
    }

    private function appendNotes(string $baseNotes, string $appendix): string
    {
        $baseNotes = trim($baseNotes);
        $appendix = trim($appendix);

        if ($appendix === '') {
            return $baseNotes;
        }

        if ($baseNotes === '') {
            return $appendix;
        }

        return $baseNotes . "\n\n" . $appendix;
    }

    private function buildMergeHistoryLog(EloquentCollection $group, Lead $survivor, array $fieldSources): string
    {
        $lines = [
            '[Merged duplicates ' . now()->format('Y-m-d H:i:s') . ']',
            'Survivor lead #' . $survivor->id . '.',
        ];

        $pickedFrom = collect($this->mergeFieldDefinitions())
            ->map(function (array $field) use ($fieldSources, $survivor) {
                $sourceLeadId = (int) ($fieldSources[$field['key']] ?? 0);

                if ($sourceLeadId === (int) $survivor->id) {
                    return null;
                }

                return $field['label'] . ' from lead #' . $sourceLeadId;
            })
            ->filter()
            ->values();

        if ($pickedFrom->isNotEmpty()) {
            $lines[] = 'Selected picks → ' . $pickedFrom->implode(' | ');
        }

        foreach ($group as $item) {
            if ((int) $item->id === (int) $survivor->id) {
                continue;
            }

            $parts = [];

            foreach ($this->mergeFieldDefinitions() as $field) {
                $rawValue = $item->{$field['key']};

                if (!$this->fieldFilled($rawValue)) {
                    continue;
                }

                $parts[] = $field['label'] . ': ' . $this->stringifyMergeValue($rawValue);
            }

            if (!empty($parts)) {
                $lines[] = 'Lead #' . $item->id . ' alt data → ' . implode(' | ', $parts);
            }
        }

        return implode("\n", $lines);
    }

    private function buildMergedSourceSnapshot(Lead $source, Lead $survivor, array $fieldSources): string
    {
        $picked = collect($this->mergeFieldDefinitions())
            ->filter(function (array $field) use ($fieldSources, $source) {
                return (int) ($fieldSources[$field['key']] ?? 0) === (int) $source->id;
            })
            ->map(function (array $field) {
                return $field['label'];
            })
            ->values()
            ->implode(', ');

        $snapshotParts = [];

        foreach ($this->mergeFieldDefinitions() as $field) {
            $rawValue = $source->{$field['key']};

            if (!$this->fieldFilled($rawValue)) {
                continue;
            }

            $snapshotParts[] = $field['label'] . ': ' . $this->stringifyMergeValue($rawValue);
        }

        return trim(implode("\n", array_filter([
            'Merged into survivor lead #' . $survivor->id . ' on ' . now()->format('Y-m-d H:i:s') . '.',
            $picked !== '' ? 'Selected from this row: ' . $picked . '.' : null,
            !empty($snapshotParts) ? 'Source snapshot → ' . implode(' | ', $snapshotParts) : null,
        ])));
    }

    private function loadLeadCallHistoryRows(int $leadId, bool $includeDemo = false): array
    {
        return DB::table('lead_call_histories')
            ->where('lead_id', $leadId)
            ->when(!$includeDemo, function ($query) {
                $query->where('source', '!=', 'seeded_demo');
            })
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'lead_id' => $row->lead_id,
                'source' => $row->source,
                'external_call_id' => $row->external_call_id,
                'direction' => $row->direction,
                'call_status' => $row->call_status,
                'started_at' => $row->started_at,
                'ended_at' => $row->ended_at,
                'duration_seconds' => $row->duration_seconds,
                'agent_name' => $row->agent_name,
                'from_number' => $row->from_number,
                'to_number' => $row->to_number,
                'recording_url' => $row->recording_url,
                'note' => $row->note,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])
            ->values()
            ->all();
    }

    private function loadLeadSmsHistoryRows(int $leadId): array
    {
        return DB::table('lead_sms_histories')
            ->where('lead_id', $leadId)
            ->orderByDesc('message_created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'lead_id' => $row->lead_id,
                'source' => $row->source,
                'external_message_id' => $row->external_message_id,
                'direction' => $row->direction,
                'message_status' => $row->message_status,
                'message_delivery_result' => $row->message_delivery_result,
                'message_created_at' => $row->message_created_at,
                'target_type' => $row->target_type,
                'target_id' => $row->target_id,
                'target_name' => $row->target_name,
                'target_phone' => $row->target_phone,
                'contact_id' => $row->contact_id,
                'contact_name' => $row->contact_name,
                'contact_phone' => $row->contact_phone,
                'sender_id' => $row->sender_id,
                'from_number' => $row->from_number,
                'to_numbers_json' => $row->to_numbers_json,
                'is_mms' => (bool) $row->is_mms,
                'text' => $row->text,
                'webhook_received_at' => $row->webhook_received_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ])
            ->values()
            ->all();
    }
}
