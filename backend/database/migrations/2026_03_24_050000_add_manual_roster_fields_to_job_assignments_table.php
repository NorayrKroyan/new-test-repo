<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('job_assignments', 'carrier_name')) {
                $table->string('carrier_name', 25)->nullable()->after('slot_type');
            }

            if (!Schema::hasColumn('job_assignments', 'status')) {
                $table->string('status', 32)->default('open')->after('driver_name');
            }

            if (!Schema::hasColumn('job_assignments', 'slot_order')) {
                $table->unsignedInteger('slot_order')->default(0)->after('slot_type');
            }

            if (!Schema::hasColumn('job_assignments', 'source_type')) {
                $table->string('source_type')->default('manual')->index()->after('internal_poc_user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_assignments', function (Blueprint $table) {
            $drops = [];

            if (Schema::hasColumn('job_assignments', 'carrier_name')) {
                $drops[] = 'carrier_name';
            }

            if (Schema::hasColumn('job_assignments', 'status')) {
                $drops[] = 'status';
            }

            if (Schema::hasColumn('job_assignments', 'slot_order')) {
                $drops[] = 'slot_order';
            }

            if ($drops) {
                $table->dropColumn($drops);
            }
        });
    }
};
