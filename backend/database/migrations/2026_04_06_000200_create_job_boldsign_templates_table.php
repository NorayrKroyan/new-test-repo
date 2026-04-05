<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_boldsign_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_available_id')->constrained('job_availables')->cascadeOnDelete();
            $table->string('template_id');
            $table->timestamps();

            $table->unique(['job_available_id', 'template_id'], 'job_boldsign_templates_job_template_uq');
            $table->index('job_available_id', 'job_boldsign_templates_job_idx');
            $table->index('template_id', 'job_boldsign_templates_template_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_boldsign_templates');
    }
};
