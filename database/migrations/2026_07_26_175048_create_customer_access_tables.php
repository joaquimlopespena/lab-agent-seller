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
        Schema::create('customer_identities', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('customer_magic_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_identity_id')->constrained()->restrictOnDelete();
            $table->string('token_hash')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['customer_identity_id', 'expires_at']);
        });

        Schema::create('customer_sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('customer_identity_id')->constrained()->restrictOnDelete();
            $table->foreignId('active_company_id')->nullable()->constrained('companies')->restrictOnDelete();
            $table->string('token_hash')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('last_activity_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['customer_identity_id', 'expires_at']);
            $table->index(['active_company_id', 'customer_identity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_sessions');
        Schema::dropIfExists('customer_magic_links');
        Schema::dropIfExists('customer_identities');
    }
};
