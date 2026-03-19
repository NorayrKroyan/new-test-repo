<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadCallAnswer;
use App\Models\LeadCallSession;
use App\Models\QualificationScript;
use App\Models\QualificationScriptStep;
use App\Models\QualificationStepOption;
use App\Models\Stage;
use Illuminate\Support\Collection;

class QualificationOutcomeService
{
    public function resolveScriptForLead(Lead $lead): ?QualificationScript
    {
        $query = QualificationScript::query()
            ->where('is_active', true)
            ->orderBy('priority')
            ->orderByDesc('is_default')
            ->orderBy('id');

        $adName = trim((string) $lead->ad_name);
        $platform = trim((string) $lead->platform);

        $scripts = $query->get();

        if ($adName !== '') {
            $match = $scripts->first(function (QualificationScript $script) use ($adName) {
                return mb_strtolower((string) $script->applies_to_ad_name) === mb_strtolower($adName);
            });

            if ($match) {
                return $match;
            }
        }

        if ($platform !== '') {
            $match = $scripts->first(function (QualificationScript $script) use ($platform) {
                return mb_strtolower((string) $script->applies_to_platform) === mb_strtolower($platform);
            });

            if ($match) {
                return $match;
            }
        }

        return $scripts->firstWhere('is_default', true) ?: $scripts->first();
    }

    public function firstStep(QualificationScript $script): ?QualificationScriptStep
    {
        return $script->steps()->with('options')->orderBy('sort_order')->orderBy('id')->first();
    }

    public function nextStep(
        LeadCallSession $session,
        QualificationScriptStep $step,
        ?QualificationStepOption $option = null
    ): ?QualificationScriptStep {
        if ($option?->nextStep) {
            return $option->nextStep()->with('options')->first();
        }

        return $session->script
            ->steps()
            ->with('options')
            ->where(function ($query) use ($step) {
                $query->where('sort_order', '>', $step->sort_order)
                    ->orWhere(function ($nested) use ($step) {
                        $nested->where('sort_order', $step->sort_order)
                            ->where('id', '>', $step->id);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }

    public function summarize(LeadCallSession $session): array
    {
        $session->loadMissing([
            'lead',
            'answers',
            'recommendedStage',
        ]);

        $answers = $session->answers;

        $score = (int) $answers->sum('score_delta');
        $hasDisqualifier = $answers->contains(fn ($answer) => (bool) $answer->is_disqualifying);

        $recommendedStatus = null;
        $recommendedStageOrder = null;
        $recommendedStage = null;
        $qualifiesForConversion = false;
        $callResult = 'needs_follow_up';

        if ($hasDisqualifier) {
            /** @var LeadCallAnswer|null $disqualifyingAnswer */
            $disqualifyingAnswer = $answers->first(fn ($answer) => (bool) $answer->is_disqualifying);
            $recommendedStatus = $disqualifyingAnswer?->triggered_status ?: 'disqualified';
            $recommendedStageOrder = $disqualifyingAnswer?->triggered_stage_order ?: 99;
            $callResult = 'disqualified';
        } else {
            /** @var LeadCallAnswer|null $statusAnswer */
            $statusAnswer = $answers
                ->reverse()
                ->first(fn ($answer) => trim((string) $answer->triggered_status) !== '');

            /** @var LeadCallAnswer|null $stageAnswer */
            $stageAnswer = $answers
                ->reverse()
                ->first(fn ($answer) => $answer->triggered_stage_order !== null);

            $recommendedStatus = $statusAnswer?->triggered_status;
            $recommendedStageOrder = $stageAnswer?->triggered_stage_order;

            if ($recommendedStatus === null && $recommendedStageOrder === null) {
                if ($score >= 4) {
                    $recommendedStatus = 'qualified';
                    $recommendedStageOrder = 2;
                } else {
                    $recommendedStatus = 'contacted';
                    $recommendedStageOrder = 1;
                }
            }

            if ($recommendedStatus === null) {
                $recommendedStatus = $this->defaultStatusFromStageOrder($recommendedStageOrder, $score);
            }

            if ($recommendedStageOrder === null) {
                $recommendedStageOrder = $this->defaultStageOrderFromStatus($recommendedStatus);
            }

            $callResult = $this->defaultCallResultFromStatus($recommendedStatus);
        }

        $recommendedStage = $this->resolveStageForLead(
            $session->lead,
            $recommendedStageOrder
        );

        $normalizedStatus = strtolower(trim((string) $recommendedStatus));
        if (!$hasDisqualifier && in_array($normalizedStatus, ['qualified', 'ready_for_senior_rep'], true)) {
            $qualifiesForConversion = true;
        }

        return [
            'score' => $score,
            'has_disqualifier' => $hasDisqualifier,
            'recommended_status' => $recommendedStatus,
            'recommended_stage_order' => $recommendedStageOrder,
            'recommended_stage_id' => $recommendedStage?->id,
            'recommended_stage_label' => $recommendedStage
                ? trim($recommendedStage->stage_name . ' / ' . $recommendedStage->stage_group . ' / ' . $recommendedStage->stage_order)
                : null,
            'qualifies_for_conversion' => $qualifiesForConversion,
            'call_result' => $callResult,
        ];
    }

    public function resolveStageForLead(Lead $lead, ?int $stageOrder): ?Stage
    {
        if (!$stageOrder) {
            return null;
        }

        $group = trim((string) $lead->ad_name);

        if ($group !== '') {
            $stage = Stage::query()
                ->where('stage_group', $group)
                ->where('stage_order', $stageOrder)
                ->orderBy('id')
                ->first();

            if ($stage) {
                return $stage;
            }

            if ((int) $stageOrder === 99) {
                return Stage::query()->firstOrCreate(
                    [
                        'stage_group' => $group,
                        'stage_order' => 99,
                    ],
                    [
                        'stage_name' => 'Disqualified Lead',
                    ]
                );
            }
        }

        return Stage::query()
            ->where('stage_order', $stageOrder)
            ->orderBy('id')
            ->first();
    }

    private function defaultStageOrderFromStatus(?string $recommendedStatus): ?int
    {
        $normalized = strtolower(trim((string) $recommendedStatus));

        if ($normalized === '') {
            return null;
        }

        if (str_contains($normalized, 'disqual')) {
            return 99;
        }

        if (in_array($normalized, ['qualified', 'ready_for_senior_rep'], true)) {
            return 2;
        }

        if (in_array($normalized, ['contacted', 'needs_insurance', 'needs_follow_up'], true)) {
            return 1;
        }

        return null;
    }

    private function defaultStatusFromStageOrder(?int $recommendedStageOrder, int $score): string
    {
        if ((int) $recommendedStageOrder === 99) {
            return 'disqualified';
        }

        if ($recommendedStageOrder !== null && (int) $recommendedStageOrder >= 2) {
            return 'qualified';
        }

        return $score >= 4 ? 'qualified' : 'contacted';
    }

    private function defaultCallResultFromStatus(?string $recommendedStatus): string
    {
        $normalized = strtolower(trim((string) $recommendedStatus));

        if ($normalized === '') {
            return 'needs_follow_up';
        }

        if (str_contains($normalized, 'disqual')) {
            return 'disqualified';
        }

        if (in_array($normalized, ['qualified', 'ready_for_senior_rep'], true)) {
            return 'qualified';
        }

        if ($normalized === 'contacted') {
            return 'contacted';
        }

        return $normalized;
    }

    private function findAnswer(Collection $answers, string $stepKey)
    {
        return $answers->first(function ($answer) use ($stepKey) {
            return (string) $answer->step_key_snapshot === $stepKey;
        });
    }
}
