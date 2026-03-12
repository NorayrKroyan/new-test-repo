<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_availables', function (Blueprint $table) {
            if (!Schema::hasColumn('job_availables', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('customer_company')->constrained('customers')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_availables', function (Blueprint $table) {
            if (Schema::hasColumn('job_availables', 'customer_id')) {
                $table->dropConstrainedForeignId('customer_id');
            }
        });
    }
};
