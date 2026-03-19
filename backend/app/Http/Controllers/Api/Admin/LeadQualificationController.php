<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadCallAnswer;
use App\Models\LeadCallSession;
use App\Models\QualificationScript;
use App\Models\QualificationScriptStep;
use App\Models\QualificationStepOption;
use App\Services\QualificationOutcomeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadQualificationController extends Controller
{
    public function __construct(
        protected QualificationOutcomeService $outcomes
    ) {
    }

    public function index(Lead $lead)
    {
        $rows = LeadCallSession::query()
            ->with([
                'recommendedStage',
                'answers',
            ])
            ->where('lead_id', $lead->id)
            ->latest('id')
            ->get()
            ->map(fn (LeadCallSession $session) => $this->serializeSession($session));

        return response()->json([
            'data' => $rows,
        ]);
    }

    public function start(Request $request, Lead $lead)
    {
        $selectedScriptId = $request->integer('qualification_script_id');

        /** @var QualificationScript|null $script */
        $script = $selectedScriptId
            ? QualificationScript::query()
                ->where('is_active', true)
                ->find($selectedScriptId)
            : $this->outcomes->resolveScriptForLead($lead);

        if (!$script) {
            throw ValidationException::withMessages([
                'lead' => 'No active qualification script was found for this lead.',
            ]);
        }

        $normalizedLeadStatus = strtolower(trim((string) $lead->lead_status));

        $baseQuery = LeadCallSession::query()
            ->where('lead_id', $lead->id)
            ->where('qualification_script_id', $script->id);

        $activeAnsweredSession = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->has('answers')
            ->latest('id')
            ->first();

        if ($activeAnsweredSession) {
            return response()->json([
                'ok' => true,
                'data' => $this->serializeSession(
                    $activeAnsweredSession->fresh([
                        'lead',
                        'script',
                        'currentStep.options',
                        'answers',
                        'recommendedStage',
                    ])
                ),
            ]);
        }

        $activeSession = (clone $baseQuery)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        if ($activeSession) {
            return response()->json([
                'ok' => true,
                'data' => $this->serializeSession(
                    $activeSession->fresh([
                        'lead',
                        'script',
                        'currentStep.options',
                        'answers',
                        'recommendedStage',
                    ])
                ),
            ]);
        }

        $isTerminalLead = in_array($normalizedLeadStatus, [
            'qualified',
            'disqualified',
            'converted_to_carrier',
        ], true);

        if ($isTerminalLead) {
            $historicalSession = (clone $baseQuery)
                ->has('answers')
                ->latest('id')
                ->first();

            if ($historicalSession) {
                return response()->json([
                    'ok' => true,
                    'data' => $this->serializeSession(
                        $historicalSession->fresh([
                            'lead',
                            'script',
                            'currentStep.options',
                            'answers',
                            'recommendedStage',
                        ])
                    ),
                ]);
            }
        }

        $firstStep = $this->outcomes->firstStep($script);

        $session = LeadCallSession::create([
            'lead_id' => $lead->id,
            'qualification_script_id' => $script->id,
            'admin_user_id' => optional($request->user())->id,
            'current_step_id' => $firstStep?->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeSession(
                $session->fresh([
                    'lead',
                    'script',
                    'currentStep.options',
                    'answers',
                    'recommendedStage',
                ])
            ),
        ]);
    }

    public function show(LeadCallSession $session)
    {
        $session->load([
            'lead',
            'script',
            'currentStep.options',
            'answers',
            'recommendedStage',
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeSession($session),
        ]);
    }

    public function saveAnswer(Request $request, LeadCallSession $session)
    {
        if ($session->status !== 'in_progress') {
            throw ValidationException::withMessages([
                'session' => 'Only in-progress sessions can accept answers.',
            ]);
        }

        $data = $request->validate([
            'step_id' => ['required', 'integer'],
            'option_id' => ['nullable', 'integer'],
            'answer_value' => ['nullable'],
            'answer_text' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
        ]);

        $normalizedAnswerValue = array_key_exists('answer_value', $data) && $data['answer_value'] !== null
            ? (string) $data['answer_value']
            : null;

        $session->loadMissing('script');

        /** @var QualificationScriptStep $step */
        $step = QualificationScriptStep::query()
            ->with('options')
            ->where('qualification_script_id', $session->qualification_script_id)
            ->findOrFail($data['step_id']);

        /** @var QualificationStepOption|null $option */
        $option = null;
        if (!empty($data['option_id'])) {
            $option = QualificationStepOption::query()
                ->where('qualification_script_step_id', $step->id)
                ->findOrFail($data['option_id']);
        }

        if ($step->step_type === 'single_select' && !$option) {
            throw ValidationException::withMessages([
                'option_id' => 'Please choose an answer option.',
            ]);
        }

        if ($option?->requires_note && trim((string) ($data['note'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'note' => 'This answer requires a note.',
            ]);
        }

        DB::transaction(function () use ($session, $step, $option, $data, $normalizedAnswerValue) {
            LeadCallAnswer::query()->updateOrCreate(
                [
                    'lead_call_session_id' => $session->id,
                    'qualification_script_step_id' => $step->id,
                ],
                [
                    'triggered_stage_id' => null,
                    'step_key_snapshot' => $step->step_key,
                    'prompt_snapshot' => $step->prompt_text,
                    'answer_value' => $option?->value ?? $normalizedAnswerValue,
                    'answer_label' => $option?->label,
                    'answer_text' => $data['answer_text'] ?? $data['note'] ?? null,
                    'score_delta' => $option?->score_delta ?? 0,
                    'is_disqualifying' => (bool) ($option?->disqualifies_lead || $step->disqualifies_lead),
                    'triggered_status' => $option?->recommended_status ?: $step->recommended_status,
                    'triggered_stage_order' => $option?->recommended_stage_order ?: $step->recommended_stage_order,
                    'answered_at' => now(),
                ]
            );

            $nextStep = $this->outcomes->nextStep($session->fresh('script'), $step, $option);

            $isTerminal = (bool) ($step->is_terminal || $option?->disqualifies_lead || !$nextStep);

            $session->update([
                'current_step_id' => $isTerminal ? null : $nextStep?->id,
                'notes' => $this->appendSessionNote($session->notes, $data['note'] ?? null),
            ]);
        });

        $session = $session->fresh([
            'lead',
            'script',
            'currentStep.options',
            'answers',
            'recommendedStage',
        ]);

        $summary = $this->outcomes->summarize($session);

        $session->update([
            'score' => $summary['score'],
            'recommended_status' => $summary['recommended_status'],
            'recommended_stage_order' => $summary['recommended_stage_order'],
            'recommended_stage_id' => $summary['recommended_stage_id'],
            'qualifies_for_conversion' => $summary['qualifies_for_conversion'],
            'call_result' => $summary['call_result'],
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeSession(
                $session->fresh([
                    'lead',
                    'script',
                    'currentStep.options',
                    'answers',
                    'recommendedStage',
                ])
            ),
        ]);
    }

    public function complete(LeadCallSession $session)
    {
        $session->loadMissing(['lead', 'answers']);

        if ($session->status !== 'in_progress') {
            return response()->json([
                'ok' => true,
                'data' => $this->serializeSession(
                    $session->fresh([
                        'lead',
                        'script',
                        'currentStep.options',
                        'answers',
                        'recommendedStage',
                    ])
                ),
            ]);
        }

        $summary = $this->outcomes->summarize($session);

        $session->update([
            'status' => $summary['has_disqualifier'] ? 'disqualified' : 'completed',
            'score' => $summary['score'],
            'recommended_status' => $summary['recommended_status'],
            'recommended_stage_order' => $summary['recommended_stage_order'],
            'recommended_stage_id' => $summary['recommended_stage_id'],
            'qualifies_for_conversion' => $summary['qualifies_for_conversion'],
            'call_result' => $summary['call_result'],
            'ended_at' => now(),
            'current_step_id' => null,
        ]);

        return response()->json([
            'ok' => true,
            'data' => $this->serializeSession(
                $session->fresh([
                    'lead',
                    'script',
                    'currentStep.options',
                    'answers',
                    'recommendedStage',
                ])
            ),
        ]);
    }

    public function applyRecommendedStage(LeadCallSession $session)
    {
        $session->loadMissing(['lead', 'recommendedStage']);

        if (!$session->recommended_stage_id) {
            throw ValidationException::withMessages([
                'session' => 'There is no recommended stage to apply.',
            ]);
        }

        $lead = $session->lead;

        $updates = [
            'lead_stage_id' => $session->recommended_stage_id,
        ];

        $normalizedRecommendedLeadStatus = $this->normalizeRecommendedLeadStatus(
            $session->recommended_status,
            $session->recommended_stage_order
        );

        if ($normalizedRecommendedLeadStatus !== null) {
            $updates['lead_status'] = $normalizedRecommendedLeadStatus;
        } elseif ($lead->lead_status === 'new') {
            $updates['lead_status'] = 'contacted';
        }

        $lead->update($updates);

        $qualificationNote = trim(implode("\n", array_filter([
            'Qualification session #' . $session->id . ' applied.',
            'Result: ' . ($session->recommended_status ?: 'n/a'),
            $session->recommendedStage
                ? 'Stage: ' . $session->recommendedStage->stage_name . ' / ' . $session->recommendedStage->stage_group . ' / ' . $session->recommendedStage->stage_order
                : null,
        ])));

        if ($qualificationNote !== '') {
            $lead->update([
                'notes' => trim(implode("\n\n", array_filter([
                    (string) $lead->notes,
                    $qualificationNote,
                ]))),
            ]);
        }

        return response()->json([
            'ok' => true,
            'lead' => $lead->fresh(['stage']),
            'session' => $this->serializeSession(
                $session->fresh([
                    'lead',
                    'script',
                    'currentStep.options',
                    'answers',
                    'recommendedStage',
                ])
            ),
        ]);
    }

    private function normalizeRecommendedLeadStatus(?string $recommendedStatus, ?int $recommendedStageOrder): ?string
    {
        $key = strtolower(trim((string) $recommendedStatus));

        if ($key === '') {
            return $recommendedStageOrder === 99 ? 'disqualified' : null;
        }

        if (in_array($key, ['qualified', 'ready_for_senior_rep'], true)) {
            return 'qualified';
        }

        if ($key === 'contacted' || $key === 'needs_insurance') {
            return 'contacted';
        }

        if ($key === 'new') {
            return 'new';
        }

        if (str_contains($key, 'disqual') || $recommendedStageOrder === 99) {
            return 'disqualified';
        }

        return null;
    }

    private function serializeSession(LeadCallSession $session): array
    {
        return [
            'id' => $session->id,
            'lead_id' => $session->lead_id,
            'status' => $session->status,
            'call_result' => $session->call_result,
            'score' => $session->score,
            'recommended_status' => $session->recommended_status,
            'recommended_stage_id' => $session->recommended_stage_id,
            'recommended_stage_order' => $session->recommended_stage_order,
            'recommended_stage_label' => $session->recommendedStage
                ? trim($session->recommendedStage->stage_name . ' / ' . $session->recommendedStage->stage_group . ' / ' . $session->recommendedStage->stage_order)
                : null,
            'qualifies_for_conversion' => (bool) $session->qualifies_for_conversion,
            'started_at' => optional($session->started_at)->toDateTimeString(),
            'ended_at' => optional($session->ended_at)->toDateTimeString(),
            'notes' => $session->notes,
            'lead' => $session->relationLoaded('lead') && $session->lead ? [
                'id' => $session->lead->id,
                'full_name' => $session->lead->full_name,
                'ad_name' => $session->lead->ad_name,
                'platform' => $session->lead->platform,
                'lead_status' => $session->lead->lead_status,
                'lead_stage_id' => $session->lead->lead_stage_id,
            ] : null,
            'script' => $session->relationLoaded('script') && $session->script ? [
                'id' => $session->script->id,
                'name' => $session->script->name,
                'slug' => $session->script->slug,
                'version' => $session->script->version,
            ] : null,
            'current_step' => $session->relationLoaded('currentStep') && $session->currentStep ? [
                'id' => $session->currentStep->id,
                'step_key' => $session->currentStep->step_key,
                'title' => $session->currentStep->title,
                'prompt_text' => $session->currentStep->prompt_text,
                'question_text' => $session->currentStep->prompt_text ?: $session->currentStep->title ?: $session->currentStep->step_key,
                'help_text' => $session->currentStep->help_text,
                'step_type' => $session->currentStep->step_type,
                'input_type' => $session->currentStep->step_type,
                'answer_input_type' => $session->currentStep->step_type,
                'options' => $session->currentStep->options->map(fn ($option) => [
                    'id' => $option->id,
                    'label' => $option->label,
                    'option_label' => $option->label,
                    'value' => $option->value,
                    'option_value' => $option->value,
                    'requires_note' => (bool) $option->requires_note,
                ])->values()->all(),
            ] : null,
            'answers' => $session->relationLoaded('answers')
                ? $session->answers->map(function ($answer) {
                    $stepId = $answer->qualification_script_step_id ?? null;
                    $questionText = $answer->prompt_snapshot ?: $answer->step_key_snapshot ?: null;
                    $optionLabel = $answer->answer_label ?: null;
                    $optionValue = $answer->answer_value;

                    return [
                        'id' => $answer->id,
                        'step_id' => $stepId,
                        'qualification_script_step_id' => $stepId,
                        'step_key_snapshot' => $answer->step_key_snapshot,
                        'prompt_snapshot' => $answer->prompt_snapshot,
                        'answer_value' => $answer->answer_value,
                        'answer_label' => $answer->answer_label,
                        'answer_text' => $answer->answer_text,
                        'score_delta' => $answer->score_delta,
                        'is_disqualifying' => (bool) $answer->is_disqualifying,
                        'triggered_status' => $answer->triggered_status,
                        'triggered_stage_order' => $answer->triggered_stage_order,
                        'answered_at' => optional($answer->answered_at)->toDateTimeString(),
                        'step' => [
                            'id' => $stepId,
                            'step_key' => $answer->step_key_snapshot,
                            'question_text' => $questionText,
                            'title' => $questionText,
                            'label' => $questionText,
                        ],
                        'option' => $optionLabel || $optionValue !== null ? [
                            'label' => $optionLabel ?: (string) $optionValue,
                            'option_label' => $optionLabel ?: (string) $optionValue,
                            'value' => $optionValue,
                            'option_value' => $optionValue,
                        ] : null,
                    ];
                })->values()->all()
                : [],
        ];
    }

    private function appendSessionNote(?string $existing, ?string $newNote): ?string
    {
        $existing = trim((string) $existing);
        $newNote = trim((string) $newNote);

        if ($newNote === '') {
            return $existing !== '' ? $existing : null;
        }

        return trim(implode("\n\n", array_filter([$existing, $newNote])));
    }
}
