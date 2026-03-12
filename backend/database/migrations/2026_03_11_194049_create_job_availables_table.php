<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_availables', function (Blueprint $table) {
            $table->id();
            $table->string('job_number')->nullable()->index();
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('origin_city')->nullable();
            $table->string('origin_state')->nullable();
            $table->string('destination_city')->nullable();
            $table->string('destination_state')->nullable();

            $table->string('equipment_type')->nullable();
            $table->string('trailer_type')->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('rate', 10, 2)->nullable();

            $table->string('status')->default('open')->index();

            $table->string('customer_name')->nullable();
            $table->string('customer_company')->nullable();

            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('posted_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_availables');
    }
};
