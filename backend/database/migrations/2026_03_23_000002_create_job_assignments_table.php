<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_available_id')->constrained('job_availables')->cascadeOnDelete();
            $table->foreignId('carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('internal_poc_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_type')->default('carrier')->index();
            $table->string('slot_type')->default('primary')->index();
            $table->string('driver_name')->nullable();
            $table->string('truck_number')->nullable();
            $table->string('trailer_owner_type')->default('carrier');
            $table->string('trailer_id')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->timestamp('readiness_checked_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['job_available_id', 'slot_type'], 'job_assignments_job_slot_idx');
            $table->index(['job_available_id', 'internal_poc_user_id'], 'job_assignments_job_poc_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_assignments');
    }
};
