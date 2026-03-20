<?php

namespace Database\Seeders;

use App\Models\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeadCallHistorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $agentPool = [
            'Dispatch Desk',
            'Carrier Recruiting',
            'Dialpad Demo Queue',
        ];
        $statusPool = ['completed', 'missed', 'voicemail', 'completed'];

        Lead::query()
            ->orderByDesc('id')
            ->get()
            ->each(function (Lead $lead) use ($now, $agentPool, $statusPool) {
                $baseName = trim((string) ($lead->full_name ?: 'Lead #' . $lead->id));
                $leadPhone = trim((string) ($lead->phone ?: ''));
                $fallbackPhone = '+1555' . str_pad((string) (100000 + ($lead->id % 900000)), 6, '0', STR_PAD_LEFT);
                $phone = $leadPhone !== '' ? $leadPhone : $fallbackPhone;
                $companyLine = '+1404' . str_pad((string) (550000 + ($lead->id % 4000)), 6, '0', STR_PAD_LEFT);
                $agentA = $agentPool[$lead->id % count($agentPool)];
                $agentB = $agentPool[($lead->id + 1) % count($agentPool)];
                $agentC = $agentPool[($lead->id + 2) % count($agentPool)];
                $statusA = $statusPool[$lead->id % count($statusPool)];
                $statusB = $statusPool[($lead->id + 1) % count($statusPool)];
                $statusC = $statusPool[($lead->id + 2) % count($statusPool)];
                $baseOffset = ($lead->id % 7) + 1;

                DB::table('lead_call_histories')
                    ->where('lead_id', $lead->id)
                    ->where('source', 'seeded_demo')
                    ->delete();

                $rows = [
                    [
                        'lead_id' => $lead->id,
                        'source' => 'seeded_demo',
                        'external_call_id' => 'seed-' . $lead->id . '-1',
                        'direction' => 'outbound',
                        'call_status' => $statusA,
                        'started_at' => $now->copy()->subHours($baseOffset)->subMinutes($lead->id % 37),
                        'ended_at' => $now->copy()->subHours($baseOffset)->subMinutes($lead->id % 37)->addSeconds(95 + (($lead->id * 13) % 420)),
                        'duration_seconds' => 95 + (($lead->id * 13) % 420),
                        'agent_name' => $agentA,
                        'from_number' => $companyLine,
                        'to_number' => $phone,
                        'recording_url' => null,
                        'note' => 'Seeded backend demo call for ' . $baseName . ' (lead #' . $lead->id . ').',
                        'payload_json' => json_encode([
                            'seeded' => true,
                            'seed_source' => 'LeadCallHistorySeeder',
                            'lead_id' => $lead->id,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'lead_id' => $lead->id,
                        'source' => 'seeded_demo',
                        'external_call_id' => 'seed-' . $lead->id . '-2',
                        'direction' => ($lead->id % 2 === 0) ? 'inbound' : 'outbound',
                        'call_status' => $statusB,
                        'started_at' => $now->copy()->subDays(1)->subHours($baseOffset)->subMinutes(($lead->id * 3) % 41),
                        'ended_at' => $now->copy()->subDays(1)->subHours($baseOffset)->subMinutes(($lead->id * 3) % 41)->addSeconds(18 + (($lead->id * 5) % 300)),
                        'duration_seconds' => 18 + (($lead->id * 5) % 300),
                        'agent_name' => $agentB,
                        'from_number' => ($lead->id % 2 === 0) ? $phone : $companyLine,
                        'to_number' => ($lead->id % 2 === 0) ? $companyLine : $phone,
                        'recording_url' => null,
                        'note' => 'Seeded backend follow-up record for ' . $baseName . '.',
                        'payload_json' => json_encode([
                            'seeded' => true,
                            'seed_source' => 'LeadCallHistorySeeder',
                            'lead_id' => $lead->id,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'lead_id' => $lead->id,
                        'source' => 'seeded_demo',
                        'external_call_id' => 'seed-' . $lead->id . '-3',
                        'direction' => 'outbound',
                        'call_status' => $statusC,
                        'started_at' => $now->copy()->subDays(2)->subHours($baseOffset)->subMinutes(($lead->id * 7) % 53),
                        'ended_at' => $now->copy()->subDays(2)->subHours($baseOffset)->subMinutes(($lead->id * 7) % 53)->addSeconds(42 + (($lead->id * 11) % 240)),
                        'duration_seconds' => 42 + (($lead->id * 11) % 240),
                        'agent_name' => $agentC,
                        'from_number' => $companyLine,
                        'to_number' => $phone,
                        'recording_url' => null,
                        'note' => 'Seeded backend voicemail/check-in for ' . $baseName . '.',
                        'payload_json' => json_encode([
                            'seeded' => true,
                            'seed_source' => 'LeadCallHistorySeeder',
                            'lead_id' => $lead->id,
                        ]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ];

                DB::table('lead_call_histories')->insert($rows);
            });
    }
}
