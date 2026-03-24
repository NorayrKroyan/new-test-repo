<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_availables', function (Blueprint $table) {
            $table->text('rate_description')->nullable()->after('rate');
            $table->date('job_start_date')->nullable()->after('status');
            $table->unsignedInteger('primary_required')->default(0)->after('job_start_date');
            $table->unsignedInteger('spare_allowed')->default(0)->after('primary_required');
        });
    }

    public function down(): void
    {
        Schema::table('job_availables', function (Blueprint $table) {
            $table->dropColumn([
                'rate_description',
                'job_start_date',
                'primary_required',
                'spare_allowed',
            ]);
        });
    }
};
