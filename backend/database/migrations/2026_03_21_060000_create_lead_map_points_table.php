<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_map_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('query_source', 50)->nullable()->index();
            $table->string('geocode_query')->nullable();
            $table->string('resolved_city')->nullable();
            $table->string('resolved_state', 50)->nullable()->index();
            $table->string('resolved_postal_code', 20)->nullable();
            $table->text('formatted_address')->nullable();
            $table->string('place_id')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->string('geocode_status', 50)->nullable()->index();
            $table->timestamp('geocoded_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_map_points');
    }
};
