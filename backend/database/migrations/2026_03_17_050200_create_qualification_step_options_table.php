<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qualification_step_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('qualification_script_step_id')->constrained('qualification_script_steps')->cascadeOnDelete();
            $table->foreignId('next_step_id')->nullable()->constrained('qualification_script_steps')->nullOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->integer('score_delta')->default(0);
            $table->boolean('disqualifies_lead')->default(false);
            $table->boolean('requires_note')->default(false);
            $table->string('recommended_status')->nullable();
            $table->unsignedInteger('recommended_stage_order')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qualification_step_options');
    }
};
