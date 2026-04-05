<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boldsign_template_overrides', function (Blueprint $table) {
            $table->id();
            $table->string('template_id')->unique();
            $table->string('preferred_signer_role')->nullable();
            $table->unsignedInteger('preferred_signer_role_index')->nullable();
            $table->json('field_map_json')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'preferred_signer_role'], 'boldsign_template_overrides_enabled_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boldsign_template_overrides');
    }
};
