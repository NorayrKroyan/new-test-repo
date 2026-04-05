<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_contract_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('source_type', 20); // template|upload
            $table->string('template_key')->nullable();
            $table->string('template_id')->nullable();
            $table->string('template_name')->nullable();

            $table->string('uploaded_original_name')->nullable();
            $table->string('uploaded_storage_path')->nullable();

            $table->string('recipient_name')->nullable();
            $table->string('recipient_email');
            $table->string('document_name')->nullable();
            $table->string('subject')->nullable();
            $table->text('message')->nullable();

            $table->string('boldsign_document_id')->nullable()->index();
            $table->text('boldsign_document_url')->nullable();

            $table->string('status', 40)->default('draft');
            $table->string('boldsign_status', 80)->nullable();

            $table->json('prefill_payload_json')->nullable();
            $table->json('boldsign_response_json')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contract_requests');
    }
};
