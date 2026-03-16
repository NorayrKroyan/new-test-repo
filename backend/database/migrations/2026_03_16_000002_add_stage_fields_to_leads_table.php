<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->foreignId('lead_stage_id')
                ->nullable()
                ->after('lead_status')
                ->constrained('stages')
                ->nullOnDelete();

            $table->boolean('funnel_enabled')
                ->default(true)
                ->after('lead_stage_id');

            $table->index(['lead_stage_id', 'funnel_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['lead_stage_id', 'funnel_enabled']);
            $table->dropConstrainedForeignId('lead_stage_id');
            $table->dropColumn('funnel_enabled');
        });
    }
};
