<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('duplicate_of_lead_id')->nullable()->after('linked_carrier_id');
            $table->string('duplicate_basis', 50)->nullable()->after('duplicate_of_lead_id');

            $table->index('duplicate_of_lead_id', 'idx_leads_duplicate_of_lead_id');
            $table->index('duplicate_basis', 'idx_leads_duplicate_basis');

            $table->foreign('duplicate_of_lead_id', 'fk_leads_duplicate_of_lead_id')
                ->references('id')
                ->on('leads')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign('fk_leads_duplicate_of_lead_id');
            $table->dropIndex('idx_leads_duplicate_of_lead_id');
            $table->dropIndex('idx_leads_duplicate_basis');
            $table->dropColumn(['duplicate_of_lead_id', 'duplicate_basis']);
        });
    }
};
