<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_name', 120);
            $table->string('stage_group', 120);
            $table->unsignedInteger('stage_order');
            $table->timestamps();

            $table->unique(['stage_group', 'stage_name']);
            $table->index(['stage_group', 'stage_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stages');
    }
};
