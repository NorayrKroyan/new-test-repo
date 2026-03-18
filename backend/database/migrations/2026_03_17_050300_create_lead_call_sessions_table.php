<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_call_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('qualification_script_id')->constrained('qualification_scripts')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_step_id')->nullable()->constrained('qualification_script_steps')->nullOnDelete();
            $table->foreignId('recommended_stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->string('status', 50)->default('in_progress');
            $table->string('call_result')->nullable();
            $table->string('recommended_status')->nullable();
            $table->unsignedInteger('recommended_stage_order')->nullable();
            $table->integer('score')->default(0);
            $table->boolean('qualifies_for_conversion')->default(false);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_call_sessions');
    }
};
