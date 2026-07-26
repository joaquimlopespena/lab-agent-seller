<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pipedrive_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->integer('display_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
        });

        Schema::create('pipedrive_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_pipeline_id')->constrained('pipedrive_pipelines')->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->integer('display_order')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
            $table->index(['pipedrive_pipeline_id', 'display_order']);
        });

        Schema::create('pipedrive_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id')->nullable();
            $table->string('pipedrive_key');
            $table->string('entity_type');
            $table->string('field_type');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->jsonb('options_payload')->nullable();
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'entity_type', 'pipedrive_key']);
        });

        Schema::create('pipedrive_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipedrive_users');
        Schema::dropIfExists('pipedrive_custom_fields');
        Schema::dropIfExists('pipedrive_stages');
        Schema::dropIfExists('pipedrive_pipelines');
    }
};
