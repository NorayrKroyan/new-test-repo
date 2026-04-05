<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_contract_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_contract_request_id')->nullable()->constrained('lead_contract_requests')->nullOnDelete();
            $table->string('boldsign_document_id')->nullable()->index();
            $table->string('event_name')->nullable();
            $table->boolean('signature_valid')->default(false);
            $table->json('payload_json')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('process_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contract_webhook_events');
    }
};
