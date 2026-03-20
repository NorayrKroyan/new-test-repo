<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialpad_sms_webhook_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('source', 50)->default('dialpad_sms_webhook');
            $table->string('delivery_format', 20)->nullable();
            $table->string('external_message_id', 100)->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->longText('raw_body')->nullable();
            $table->longText('jwt_token')->nullable();
            $table->json('payload_json')->nullable();
            $table->boolean('processed')->default(false);
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['external_message_id', 'source'], 'dialpad_sms_receipts_msg_source_idx');
            $table->index(['lead_id', 'processed'], 'dialpad_sms_receipts_lead_processed_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialpad_sms_webhook_receipts');
    }
};
