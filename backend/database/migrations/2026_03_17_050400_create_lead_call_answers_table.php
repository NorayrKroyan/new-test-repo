<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_call_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_call_session_id')->constrained('lead_call_sessions')->cascadeOnDelete();
            $table->foreignId('qualification_script_step_id')->constrained('qualification_script_steps')->cascadeOnDelete();
            $table->foreignId('triggered_stage_id')->nullable()->constrained('stages')->nullOnDelete();
            $table->string('step_key_snapshot');
            $table->text('prompt_snapshot');
            $table->string('answer_value')->nullable();
            $table->string('answer_label')->nullable();
            $table->text('answer_text')->nullable();
            $table->integer('score_delta')->default(0);
            $table->boolean('is_disqualifying')->default(false);
            $table->string('triggered_status')->nullable();
            $table->unsignedInteger('triggered_stage_order')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['lead_call_session_id', 'qualification_script_step_id'], 'lead_session_step_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_call_answers');
    }
};
