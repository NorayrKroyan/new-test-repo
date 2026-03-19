<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\QualificationScript;
use App\Models\QualificationScriptStep;
use App\Models\QualificationStepOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\LeadCallSession;


class QualificationScriptController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $activeOnly = $request->boolean('active_only');
        $status = trim((string) $request->query('status', ''));

        $rows = QualificationScript::query()
            ->withCount('steps')
            ->when($activeOnly, function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'active', function ($query) {
                $query->where('is_active', true);
            })
            ->when($status === 'inactive', function ($query) {
                $query->where('is_active', false);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%")
                        ->orWhere('description', 'like', "%{$q}%")
                        ->orWhere('applies_to_ad_name', 'like', "%{$q}%")
                        ->orWhere('applies_to_platform', 'like', "%{$q}%");
                });
            })
            ->orderBy('priority')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $rows->map(fn (QualificationScript $script) => $this->serializeScriptSummary($script))->values(),
        ]);
    }

    public function store(Request $request)
    {
        $payload = $this->validateScript($request);
        $payload['slug'] = $this->makeUniqueSlug($payload['slug'] ?? $payload['name']);

        if (!empty($payload['is_default'])) {
            $payload['is_active'] = true;
        }

        $script = DB::transaction(function () use ($payload) {
            if (!empty($payload['is_default'])) {
                QualificationScript::query()->update(['is_default' => false]);
            }

            return QualificationScript::query()->create($payload);
        });

        return response()->json([
            'ok' => true,
            'data' => $this->serializeScriptSummary($script->fresh(['steps'])),
        ], 201);
    }

    public function show(QualificationScript $qualificationScript)
    {
        $qualificationScript->load([
            'steps.options',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeScriptDetail($qualificationScript),
        ]);
    }

    public function update(Request $request, QualificationScript $qualificationScript)
    {
        $payload = $this->validateScript($request, $qualificationScript);
        $payload['slug'] = $this->makeUniqueSlug($payload['slug'] ?? $payload['name'], $qualificationScript->id);

        if (!empty($payload['is_default'])) {
            $payload['is_active'] = true;
        }

        DB::transaction(function () use ($qualificationScript, $payload) {
            if (!empty($payload['is_default'])) {
                QualificationScript::query()
                    ->where('id', '!=', $qualificationScript->id)
                    ->update(['is_default' => false]);
            }

            $qualificationScript->update($payload);
        });

        return response()->json([
            'ok' => true,
            'data' => $this->serializeScriptSummary($qualificationScript->fresh(['steps'])),
        ]);
    }

    public function destroy(QualificationScript $qualificationScript)
    {
        $isUsed = LeadCallSession::query()
            ->where('qualification_script_id', $qualificationScript->id)
            ->exists();

        if ($isUsed) {
            return response()->json([
                'message' => 'This qualification script cannot be deleted because it has already been used.',
            ], 422);
        }

        DB::transaction(function () use ($qualificationScript) {
            $steps = $qualificationScript->steps()->with('options')->get();

            foreach ($steps as $step) {
                $step->options()->delete();
            }

            $qualificationScript->steps()->delete();
            $qualificationScript->delete();
        });

        return response()->json([
            'message' => 'Qualification script deleted.',
        ]);
    }

    public function clone(QualificationScript $qualificationScript)
    {
        $qualificationScript->load([
            'steps.options',
        ]);

        $clone = DB::transaction(function () use ($qualificationScript) {
            $copied = QualificationScript::query()->create([
                'name' => $qualificationScript->name . ' Copy',
                'slug' => $this->makeUniqueSlug($qualificationScript->slug . '-copy'),
                'description' => $qualificationScript->description,
                'applies_to_ad_name' => $qualificationScript->applies_to_ad_name,
                'applies_to_platform' => $qualificationScript->applies_to_platform,
                'is_default' => false,
                'is_active' => $qualificationScript->is_active,
                'priority' => $qualificationScript->priority,
                'version' => (int) $qualificationScript->version,
            ]);

            $stepMap = [];

            foreach ($qualificationScript->steps as $step) {
                $newStep = QualificationScriptStep::query()->create([
                    'qualification_script_id' => $copied->id,
                    'step_key' => $step->step_key,
                    'title' => $step->title,
                    'prompt_text' => $step->prompt_text,
                    'help_text' => $step->help_text,
                    'step_type' => $step->step_type,
                    'sort_order' => $step->sort_order,
                    'is_required' => $step->is_required,
                    'is_terminal' => $step->is_terminal,
                    'disqualifies_lead' => $step->disqualifies_lead,
                    'recommended_status' => $step->recommended_status,
                    'recommended_stage_order' => $step->recommended_stage_order,
                ]);

                $stepMap[$step->id] = $newStep->id;
            }

            foreach ($qualificationScript->steps as $step) {
                $newStepId = $stepMap[$step->id] ?? null;

                if (!$newStepId) {
                    continue;
                }

                foreach ($step->options as $option) {
                    QualificationStepOption::query()->create([
                        'qualification_script_step_id' => $newStepId,
                        'next_step_id' => $option->next_step_id ? ($stepMap[$option->next_step_id] ?? null) : null,
                        'label' => $option->label,
                        'value' => $option->value,
                        'sort_order' => $option->sort_order,
                        'score_delta' => $option->score_delta,
                        'disqualifies_lead' => $option->disqualifies_lead,
                        'requires_note' => $option->requires_note,
                        'recommended_status' => $option->recommended_status,
                        'recommended_stage_order' => $option->recommended_stage_order,
                    ]);
                }
            }

            return $copied;
        });

        return response()->json([
            'ok' => true,
            'data' => $this->serializeScriptSummary($clone->fresh(['steps'])),
        ], 201);
    }

    public function saveBuilder(Request $request, QualificationScript $qualificationScript)
    {
        $data = $request->validate([
            'steps' => ['required', 'array'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.client_key' => ['nullable', 'string', 'max:120'],
            'steps.*.step_key' => ['required', 'string', 'max:120'],
            'steps.*.title' => ['nullable', 'string', 'max:160'],
            'steps.*.prompt_text' => ['nullable', 'string'],
            'steps.*.help_text' => ['nullable', 'string'],
            'steps.*.step_type' => ['required', 'string', 'max:60'],
            'steps.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'steps.*.is_required' => ['nullable', 'boolean'],
            'steps.*.is_terminal' => ['nullable', 'boolean'],
            'steps.*.disqualifies_lead' => ['nullable', 'boolean'],
            'steps.*.recommended_status' => ['nullable', 'string', 'max:120'],
            'steps.*.recommended_stage_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'steps.*.options' => ['nullable', 'array'],
            'steps.*.options.*.id' => ['nullable', 'integer'],
            'steps.*.options.*.label' => ['required', 'string', 'max:160'],
            'steps.*.options.*.value' => ['nullable', 'string', 'max:160'],
            'steps.*.options.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'steps.*.options.*.score_delta' => ['nullable', 'integer', 'min:-999', 'max:999'],
            'steps.*.options.*.disqualifies_lead' => ['nullable', 'boolean'],
            'steps.*.options.*.requires_note' => ['nullable', 'boolean'],
            'steps.*.options.*.recommended_status' => ['nullable', 'string', 'max:120'],
            'steps.*.options.*.recommended_stage_order' => ['nullable', 'integer', 'min:1', 'max:999'],
            'steps.*.options.*.next_step_id' => ['nullable'],
            'steps.*.options.*.next_step_client_key' => ['nullable', 'string', 'max:120'],
        ]);

        DB::transaction(function () use ($qualificationScript, $data) {
            $existingSteps = $qualificationScript->steps()->with('options')->get()->keyBy('id');
            $submittedSteps = collect($data['steps'] ?? [])->values();

            $savedStepIds = [];
            $stepIdsByClientKey = [];
            $stepPayloadsById = [];

            foreach ($submittedSteps as $index => $stepData) {
                $stepId = isset($stepData['id']) ? (int) $stepData['id'] : null;
                $step = $stepId && $existingSteps->has($stepId)
                    ? $existingSteps->get($stepId)
                    : new QualificationScriptStep(['qualification_script_id' => $qualificationScript->id]);

                $step->fill([
                    'step_key' => trim((string) $stepData['step_key']),
                    'title' => $this->nullableString($stepData['title'] ?? null),
                    'prompt_text' => $this->nullableString($stepData['prompt_text'] ?? null),
                    'help_text' => $this->nullableString($stepData['help_text'] ?? null),
                    'step_type' => trim((string) $stepData['step_type']),
                    'sort_order' => $this->nullableInt($stepData['sort_order'] ?? null) ?? ($index + 1),
                    'is_required' => (bool) ($stepData['is_required'] ?? false),
                    'is_terminal' => (bool) ($stepData['is_terminal'] ?? false),
                    'disqualifies_lead' => (bool) ($stepData['disqualifies_lead'] ?? false),
                    'recommended_status' => $this->nullableString($stepData['recommended_status'] ?? null),
                    'recommended_stage_order' => $this->nullableInt($stepData['recommended_stage_order'] ?? null),
                ]);
                $step->qualification_script_id = $qualificationScript->id;
                $step->save();

                $savedStepIds[] = $step->id;
                $stepPayloadsById[$step->id] = $stepData;

                $clientKey = trim((string) ($stepData['client_key'] ?? ''));
                if ($clientKey !== '') {
                    $stepIdsByClientKey[$clientKey] = $step->id;
                }
            }

            $stepIdsToDelete = $existingSteps->keys()->diff($savedStepIds)->values();
            if ($stepIdsToDelete->isNotEmpty()) {
                QualificationStepOption::query()
                    ->whereIn('qualification_script_step_id', $stepIdsToDelete)
                    ->delete();

                QualificationScriptStep::query()
                    ->whereIn('id', $stepIdsToDelete)
                    ->delete();
            }

            $freshSteps = QualificationScriptStep::query()
                ->where('qualification_script_id', $qualificationScript->id)
                ->with('options')
                ->get()
                ->keyBy('id');

            foreach ($savedStepIds as $stepId) {
                /** @var QualificationScriptStep|null $step */
                $step = $freshSteps->get($stepId);
                if (!$step) {
                    continue;
                }

                $stepData = $stepPayloadsById[$stepId] ?? [];
                $existingOptions = $step->options->keyBy('id');
                $submittedOptions = collect($stepData['options'] ?? [])->values();
                $savedOptionIds = [];

                foreach ($submittedOptions as $optionIndex => $optionData) {
                    $optionId = isset($optionData['id']) ? (int) $optionData['id'] : null;
                    $option = $optionId && $existingOptions->has($optionId)
                        ? $existingOptions->get($optionId)
                        : new QualificationStepOption(['qualification_script_step_id' => $step->id]);

                    $option->fill([
                        'label' => trim((string) $optionData['label']),
                        'value' => $this->nullableString($optionData['value'] ?? null),
                        'sort_order' => $this->nullableInt($optionData['sort_order'] ?? null) ?? ($optionIndex + 1),
                        'score_delta' => (int) ($optionData['score_delta'] ?? 0),
                        'disqualifies_lead' => (bool) ($optionData['disqualifies_lead'] ?? false),
                        'requires_note' => (bool) ($optionData['requires_note'] ?? false),
                        'recommended_status' => $this->nullableString($optionData['recommended_status'] ?? null),
                        'recommended_stage_order' => $this->nullableInt($optionData['recommended_stage_order'] ?? null),
                        'next_step_id' => $this->resolveNextStepId(
                            $optionData['next_step_id'] ?? null,
                            $optionData['next_step_client_key'] ?? null,
                            $stepIdsByClientKey,
                            $savedStepIds
                        ),
                    ]);
                    $option->qualification_script_step_id = $step->id;
                    $option->save();

                    $savedOptionIds[] = $option->id;
                }

                $optionIdsToDelete = $existingOptions->keys()->diff($savedOptionIds)->values();
                if ($optionIdsToDelete->isNotEmpty()) {
                    QualificationStepOption::query()
                        ->whereIn('id', $optionIdsToDelete)
                        ->delete();
                }
            }
        });

        $qualificationScript->load([
            'steps.options',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeScriptDetail($qualificationScript->fresh(['steps.options'])),
        ]);
    }

    private function validateScript(Request $request, ?QualificationScript $qualificationScript = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => [
                'nullable',
                'string',
                'max:160',
                Rule::unique('qualification_scripts', 'slug')->ignore($qualificationScript?->id),
            ],
            'description' => ['nullable', 'string'],
            'applies_to_ad_name' => ['nullable', 'string', 'max:160'],
            'applies_to_platform' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'version' => ['nullable', 'integer', 'min:1', 'max:9999'],
        ]);

        $data['slug'] = $this->nullableString($data['slug'] ?? null);
        $data['description'] = $this->nullableString($data['description'] ?? null);
        $data['applies_to_ad_name'] = $this->nullableString($data['applies_to_ad_name'] ?? null);
        $data['applies_to_platform'] = $this->nullableString($data['applies_to_platform'] ?? null);
        $data['priority'] = $this->nullableInt($data['priority'] ?? null) ?? 0;
        $data['version'] = $this->nullableInt($data['version'] ?? null) ?? 1;
        $data['is_default'] = (bool) ($data['is_default'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }

    private function serializeScriptSummary(QualificationScript $script): array
    {
        return [
            'id' => $script->id,
            'name' => $script->name,
            'slug' => $script->slug,
            'description' => $script->description,
            'applies_to_ad_name' => $script->applies_to_ad_name,
            'applies_to_platform' => $script->applies_to_platform,
            'is_default' => (bool) $script->is_default,
            'is_active' => (bool) $script->is_active,
            'priority' => (int) $script->priority,
            'version' => (int) $script->version,
            'steps_count' => isset($script->steps_count)
                ? (int) $script->steps_count
                : $script->steps()->count(),
        ];
    }

    private function serializeScriptDetail(QualificationScript $script): array
    {
        return [
            ...$this->serializeScriptSummary($script),
            'steps' => $script->steps
                ->values()
                ->map(function (QualificationScriptStep $step) {
                    return [
                        'id' => $step->id,
                        'client_key' => 'id:' . $step->id,
                        'step_key' => $step->step_key,
                        'title' => $step->title,
                        'prompt_text' => $step->prompt_text,
                        'help_text' => $step->help_text,
                        'step_type' => $step->step_type,
                        'sort_order' => $step->sort_order,
                        'is_required' => (bool) $step->is_required,
                        'is_terminal' => (bool) $step->is_terminal,
                        'disqualifies_lead' => (bool) $step->disqualifies_lead,
                        'recommended_status' => $step->recommended_status,
                        'recommended_stage_order' => $step->recommended_stage_order,
                        'options' => $step->options
                            ->values()
                            ->map(function (QualificationStepOption $option) {
                                return [
                                    'id' => $option->id,
                                    'label' => $option->label,
                                    'value' => $option->value,
                                    'sort_order' => $option->sort_order,
                                    'score_delta' => $option->score_delta,
                                    'disqualifies_lead' => (bool) $option->disqualifies_lead,
                                    'requires_note' => (bool) $option->requires_note,
                                    'recommended_status' => $option->recommended_status,
                                    'recommended_stage_order' => $option->recommended_stage_order,
                                    'next_step_id' => $option->next_step_id,
                                ];
                            })
                            ->all(),
                    ];
                })
                ->all(),
        ];
    }

    private function resolveNextStepId($nextStepId, ?string $nextStepClientKey, array $stepIdsByClientKey, array $savedStepIds): ?int
    {
        $parsedStepId = $this->nullableInt($nextStepId);
        if ($parsedStepId && in_array($parsedStepId, $savedStepIds, true)) {
            return $parsedStepId;
        }

        $clientKey = trim((string) $nextStepClientKey);
        if ($clientKey !== '' && array_key_exists($clientKey, $stepIdsByClientKey)) {
            return (int) $stepIdsByClientKey[$clientKey];
        }

        return null;
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function makeUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = 'qualification-script';
        }

        $slug = $base;
        $suffix = 2;

        while (
        QualificationScript::query()
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()
        ) {
            $slug = $base . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}
