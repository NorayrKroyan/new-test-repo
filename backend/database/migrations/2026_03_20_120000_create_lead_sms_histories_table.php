<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sms_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->string('source', 50)->default('dialpad_sms_webhook');
            $table->string('external_message_id', 100);
            $table->string('direction', 25)->nullable();
            $table->string('message_status', 50)->nullable();
            $table->string('message_delivery_result', 50)->nullable();
            $table->timestamp('message_created_at')->nullable();
            $table->string('target_type', 50)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('target_name')->nullable();
            $table->string('target_phone', 50)->nullable();
            $table->string('contact_id', 255)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('sender_id', 100)->nullable();
            $table->string('from_number', 50)->nullable();
            $table->json('to_numbers_json')->nullable();
            $table->boolean('is_mms')->default(false);
            $table->longText('text')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_message_id'], 'lead_sms_histories_source_msg_uq');
            $table->index(['lead_id', 'message_created_at'], 'lead_sms_histories_lead_created_idx');
            $table->index('contact_phone', 'lead_sms_histories_contact_phone_idx');
            $table->index('target_phone', 'lead_sms_histories_target_phone_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sms_histories');
    }
};
