<?php

namespace Database\Seeders;

use App\Models\Stage;
use Illuminate\Database\Seeder;

class StageSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['stage_name' => 'New Lead', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 1],
            ['stage_name' => 'Communication Established', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 2],
            ['stage_name' => 'Lead Profile Complete', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 3],
            ['stage_name' => 'Agreements Sent', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 4],
            ['stage_name' => 'Closed Lead Win', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 5],
            ['stage_name' => 'Ghosting Lead', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 98],
            ['stage_name' => 'Disqualified Lead', 'stage_group' => 'Texas Hopper Leads', 'stage_order' => 99],
        ];

        foreach ($rows as $row) {
            Stage::updateOrCreate(
                [
                    'stage_group' => $row['stage_group'],
                    'stage_name' => $row['stage_name'],
                ],
                [
                    'stage_order' => $row['stage_order'],
                ]
            );
        }
    }
}
