<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_script_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_script_id')->constrained('qualification_scripts')->cascadeOnDelete();
            $table->string('step_key');
            $table->string('title');
            $table->text('prompt_text');
            $table->text('help_text')->nullable();
            $table->string('step_type', 50)->default('single_select'); // info, single_select, number, text, boolean
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('disqualifies_lead')->default(false);
            $table->string('recommended_status')->nullable();
            $table->unsignedInteger('recommended_stage_order')->nullable();
            $table->timestamps();

            $table->unique(['qualification_script_id', 'step_key'], 'qss_script_stepkey_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualification_script_steps');
    }
};
