<?php

namespace Database\Seeders;

use App\Models\QualificationScript;
use App\Models\QualificationScriptStep;
use App\Models\QualificationStepOption;
use Illuminate\Database\Seeder;

class QualificationScriptSeeder extends Seeder
{
    public function run(): void
    {
        $script = QualificationScript::query()->updateOrCreate(
            ['slug' => 'frac-sand-fast-follow-up'],
            [
                'name' => 'Frac Sand Fast Follow-Up',
                'description' => 'DB-driven qualification script for fast follow-up carrier qualification calls.',
                'applies_to_platform' => 'fb',
                'is_default' => true,
                'is_active' => true,
                'priority' => 10,
                'version' => 1,
            ]
        );

        $steps = [
            [
                'step_key' => 'business_type',
                'title' => 'Business Type',
                'prompt_text' => 'Are you a company driver looking for a job, or are you a carrier running under your own DOT/MC authority?',
                'step_type' => 'single_select',
                'sort_order' => 10,
            ],
            [
                'step_key' => 'region_preference',
                'title' => 'Region Preference',
                'prompt_text' => 'Are you looking to stay South, or are you comfortable taking North Dakota / winter-weather work?',
                'step_type' => 'single_select',
                'sort_order' => 20,
            ],
            [
                'step_key' => 'irp_status',
                'title' => 'IRP / Plates',
                'prompt_text' => 'Are you running apportioned / IRP plates?',
                'step_type' => 'single_select',
                'sort_order' => 30,
            ],
            [
                'step_key' => 'fleet_size_total',
                'title' => 'Fleet Size',
                'prompt_text' => 'How many total trucks are in your fleet right now?',
                'step_type' => 'number',
                'sort_order' => 40,
            ],
            [
                'step_key' => 'fleet_size_commit',
                'title' => 'Committed Units',
                'prompt_text' => 'How many trucks are you ready to commit to frac work?',
                'step_type' => 'number',
                'sort_order' => 50,
            ],
            [
                'step_key' => 'drivers_ready_now',
                'title' => 'Drivers Ready',
                'prompt_text' => 'Do you already have drivers seated and ready today?',
                'step_type' => 'single_select',
                'sort_order' => 60,
            ],
            [
                'step_key' => 'sand_experience',
                'title' => 'Sand Experience',
                'prompt_text' => 'Have you hauled frac sand before?',
                'step_type' => 'single_select',
                'sort_order' => 70,
            ],
            [
                'step_key' => 'pec_status',
                'title' => 'PEC / SafeLand',
                'prompt_text' => 'Do you or your drivers have active PEC / SafeLand cards?',
                'step_type' => 'single_select',
                'sort_order' => 80,
            ],
            [
                'step_key' => 'h2s_status',
                'title' => 'H2S',
                'prompt_text' => 'Do you or your drivers have active H2S cards?',
                'step_type' => 'single_select',
                'sort_order' => 90,
            ],
            [
                'step_key' => 'trailer_interchange_status',
                'title' => 'Trailer Interchange',
                'prompt_text' => 'Do you already have a Trailer Interchange / Non-Owned Trailer rider on your policy?',
                'step_type' => 'single_select',
                'sort_order' => 100,
            ],
            [
                'step_key' => 'earliest_start',
                'title' => 'Earliest Start',
                'prompt_text' => 'When is the soonest you want to have your first truck on the dirt?',
                'step_type' => 'text',
                'sort_order' => 110,
            ],
            [
                'step_key' => 'final_call_booked',
                'title' => 'Final Interview Booked',
                'prompt_text' => 'Did the lead agree to a final Senior Representative call time?',
                'step_type' => 'single_select',
                'sort_order' => 120,
                'recommended_status' => 'ready_for_senior_rep',
                'recommended_stage_order' => 3,
            ],
            [
                'step_key' => 'rep_notes',
                'title' => 'Rep Notes',
                'prompt_text' => 'Add any final call notes.',
                'step_type' => 'text',
                'sort_order' => 130,
                'is_terminal' => true,
            ],
        ];

        $stepModels = [];

        foreach ($steps as $stepData) {
            $stepModels[$stepData['step_key']] = QualificationScriptStep::query()->updateOrCreate(
                [
                    'qualification_script_id' => $script->id,
                    'step_key' => $stepData['step_key'],
                ],
                $stepData + [
                    'qualification_script_id' => $script->id,
                    'help_text' => null,
                    'is_required' => true,
                    'is_terminal' => $stepData['is_terminal'] ?? false,
                    'disqualifies_lead' => false,
                ]
            );
        }

        $options = [
            'business_type' => [
                [
                    'label' => 'Company Driver',
                    'value' => 'company_driver',
                    'score_delta' => -100,
                    'disqualifies_lead' => true,
                    'recommended_status' => 'disqualified_company_driver',
                ],
                [
                    'label' => 'Owner Operator - No Authority',
                    'value' => 'owner_operator_no_authority',
                    'score_delta' => -100,
                    'disqualifies_lead' => true,
                    'recommended_status' => 'disqualified_no_authority',
                ],
                [
                    'label' => 'Carrier With Authority',
                    'value' => 'carrier_with_authority',
                    'score_delta' => 2,
                    'next_step_id' => $stepModels['region_preference']->id,
                ],
                [
                    'label' => 'Fleet Owner',
                    'value' => 'fleet_owner',
                    'score_delta' => 2,
                    'next_step_id' => $stepModels['region_preference']->id,
                ],
            ],
            'region_preference' => [
                ['label' => 'South / TX-LA-OK', 'value' => 'south', 'score_delta' => 1],
                ['label' => 'North / ND-WY-CO', 'value' => 'north', 'score_delta' => 1],
            ],
            'irp_status' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0],
            ],
            'drivers_ready_now' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0],
            ],
            'sand_experience' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0],
            ],
            'pec_status' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0],
            ],
            'h2s_status' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0],
            ],
            'trailer_interchange_status' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 1],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0, 'recommended_status' => 'needs_insurance', 'recommended_stage_order' => 2],
            ],
            'final_call_booked' => [
                ['label' => 'Yes', 'value' => 'yes', 'score_delta' => 2, 'recommended_status' => 'ready_for_senior_rep', 'recommended_stage_order' => 3],
                ['label' => 'No', 'value' => 'no', 'score_delta' => 0, 'recommended_status' => 'contacted', 'recommended_stage_order' => 1],
            ],
        ];

        foreach ($options as $stepKey => $stepOptions) {
            $step = $stepModels[$stepKey];

            foreach ($stepOptions as $index => $optionData) {
                QualificationStepOption::query()->updateOrCreate(
                    [
                        'qualification_script_step_id' => $step->id,
                        'value' => $optionData['value'],
                    ],
                    [
                        'qualification_script_step_id' => $step->id,
                        'label' => $optionData['label'],
                        'value' => $optionData['value'],
                        'sort_order' => ($index + 1) * 10,
                        'score_delta' => $optionData['score_delta'] ?? 0,
                        'disqualifies_lead' => $optionData['disqualifies_lead'] ?? false,
                        'requires_note' => $optionData['requires_note'] ?? false,
                        'recommended_status' => $optionData['recommended_status'] ?? null,
                        'recommended_stage_order' => $optionData['recommended_stage_order'] ?? null,
                        'next_step_id' => $optionData['next_step_id'] ?? null,
                    ]
                );
            }
        }
    }
}
