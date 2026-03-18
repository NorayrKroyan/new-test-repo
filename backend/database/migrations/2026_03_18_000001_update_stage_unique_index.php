<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropUnique('stages_stage_group_stage_name_unique');
            $table->unique(
                ['stage_group', 'stage_name', 'stage_order'],
                'stages_group_name_order_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropUnique('stages_group_name_order_unique');
            $table->unique(
                ['stage_group', 'stage_name'],
                'stages_stage_group_stage_name_unique'
            );
        });
    }
};
