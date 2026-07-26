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
        Schema::create('company_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_identity_id')->constrained()->restrictOnDelete();
            $table->foreignId('pipedrive_person_id')->constrained('pipedrive_people')->restrictOnDelete();
            $table->foreignId('pipedrive_person_email_id')->constrained('pipedrive_person_emails')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'customer_identity_id']);
            $table->unique(['company_id', 'pipedrive_person_email_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_customers');
    }
};
