<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boldsign_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_id')->unique();
            $table->string('template_name');
            $table->string('created_by_name')->nullable();
            $table->string('template_type', 100)->nullable();
            $table->json('roles_json')->nullable();
            $table->json('shared_with_teams_json')->nullable();
            $table->json('raw_payload_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_modified_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'template_name'], 'boldsign_templates_active_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boldsign_templates');
    }
};
