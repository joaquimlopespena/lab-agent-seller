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
        Schema::create('pipedrive_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_pipeline_id')->nullable()->constrained('pipedrive_pipelines')->restrictOnDelete();
            $table->foreignId('pipedrive_stage_id')->nullable()->constrained('pipedrive_stages')->restrictOnDelete();
            $table->foreignId('pipedrive_person_id')->nullable()->constrained('pipedrive_people')->restrictOnDelete();
            $table->foreignId('pipedrive_organization_id')->nullable()->constrained('pipedrive_organizations')->restrictOnDelete();
            $table->foreignId('owner_pipedrive_user_id')->nullable()->constrained('pipedrive_users')->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('title');
            $table->decimal('value', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
            $table->index(['company_id', 'pipedrive_pipeline_id', 'pipedrive_stage_id']);
        });

        Schema::create('pipedrive_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
        });

        Schema::create('pipedrive_deal_products', function (Blueprint $table) {
            $table->foreignId('pipedrive_deal_id')->constrained('pipedrive_deals')->restrictOnDelete();
            $table->foreignId('pipedrive_product_id')->constrained('pipedrive_products')->restrictOnDelete();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->timestamps();

            $table->primary(['pipedrive_deal_id', 'pipedrive_product_id']);
        });

        Schema::create('pipedrive_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_deal_id')->nullable()->constrained('pipedrive_deals')->restrictOnDelete();
            $table->foreignId('pipedrive_person_id')->nullable()->constrained('pipedrive_people')->restrictOnDelete();
            $table->foreignId('pipedrive_organization_id')->nullable()->constrained('pipedrive_organizations')->restrictOnDelete();
            $table->foreignId('owner_pipedrive_user_id')->nullable()->constrained('pipedrive_users')->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('subject');
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_done')->default(false);
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
        Schema::dropIfExists('pipedrive_activities');
        Schema::dropIfExists('pipedrive_deal_products');
        Schema::dropIfExists('pipedrive_products');
        Schema::dropIfExists('pipedrive_deals');
    }
};
