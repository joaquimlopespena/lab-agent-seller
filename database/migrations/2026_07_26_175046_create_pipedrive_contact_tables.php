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
        Schema::create('pipedrive_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->foreignId('owner_pipedrive_user_id')->nullable()->constrained('pipedrive_users')->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
        });

        Schema::create('pipedrive_people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('crm_connection_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_organization_id')->nullable()->constrained('pipedrive_organizations')->restrictOnDelete();
            $table->foreignId('owner_pipedrive_user_id')->nullable()->constrained('pipedrive_users')->restrictOnDelete();
            $table->unsignedBigInteger('pipedrive_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->jsonb('raw_payload');
            $table->timestamp('synced_at');
            $table->timestamps();

            $table->unique(['company_id', 'pipedrive_id']);
        });

        Schema::create('pipedrive_person_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_person_id')->constrained('pipedrive_people')->restrictOnDelete();
            $table->string('email');
            $table->string('label')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['company_id', 'email']);
            $table->unique(['pipedrive_person_id', 'email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipedrive_person_emails');
        Schema::dropIfExists('pipedrive_people');
        Schema::dropIfExists('pipedrive_organizations');
    }
};
