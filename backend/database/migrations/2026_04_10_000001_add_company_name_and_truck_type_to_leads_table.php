<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'company_name')) {
                $table->string('company_name')->nullable()->after('full_name');
            }

            if (!Schema::hasColumn('leads', 'truck_type')) {
                $table->string('truck_type')->nullable()->after('state');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $drop = [];

            if (Schema::hasColumn('leads', 'company_name')) {
                $drop[] = 'company_name';
            }

            if (Schema::hasColumn('leads', 'truck_type')) {
                $drop[] = 'truck_type';
            }

            if ($drop !== []) {
                $table->dropColumn($drop);
            }
        });
    }
};
