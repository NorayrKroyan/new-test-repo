<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('source_name')->nullable();
            $table->string('ad_name')->nullable();
            $table->string('platform')->nullable();
            $table->timestamp('source_created_at')->nullable();
            $table->string('lead_date_choice')->nullable();
            $table->string('insurance_answer')->nullable();

            $table->string('full_name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('carrier_class')->nullable();
            $table->string('usdot')->nullable();
            $table->integer('truck_count')->nullable();
            $table->integer('trailer_count')->nullable();

            $table->string('lead_status')->default('new')->index();
            $table->text('notes')->nullable();
            $table->json('raw_payload')->nullable();

            $table->foreignId('linked_carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->foreignId('assigned_admin_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->decimal('sold_amount', 10, 2)->nullable();
            $table->decimal('referral_fee', 10, 2)->nullable();
            $table->timestamp('sold_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['platform', 'lead_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
