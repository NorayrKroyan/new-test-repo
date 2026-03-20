<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_call_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('source', 50)->default('demo');
            $table->string('external_call_id')->nullable()->index();
            $table->string('direction', 30)->nullable()->index();
            $table->string('call_status', 30)->nullable()->index();
            $table->dateTime('started_at')->nullable()->index();
            $table->dateTime('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('agent_name')->nullable();
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('note')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'started_at'], 'lead_call_histories_lead_started_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_call_histories');
    }
};
