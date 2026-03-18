<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            LibertyBoxesLeadsSeeder::class,
            LeadSeeder::class,
            HaskellHaulLeadsSeeder::class,
            QualificationScriptSeeder::class,
        ]);
    }
}
