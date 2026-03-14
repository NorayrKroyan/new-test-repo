<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('merged_at')->nullable()->after('duplicate_basis');
            $table->unsignedBigInteger('merged_by_user_id')->nullable()->after('merged_at');
            $table->text('merge_notes')->nullable()->after('notes');

            $table->index('merged_at', 'idx_leads_merged_at');
            $table->index('merged_by_user_id', 'idx_leads_merged_by_user_id');

            $table->foreign('merged_by_user_id', 'fk_leads_merged_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign('fk_leads_merged_by_user_id');
            $table->dropIndex('idx_leads_merged_at');
            $table->dropIndex('idx_leads_merged_by_user_id');
            $table->dropColumn(['merged_at', 'merged_by_user_id', 'merge_notes']);
        });
    }
};
