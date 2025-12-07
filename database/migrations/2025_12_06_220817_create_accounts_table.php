<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accounts')) {
            Schema::create('accounts', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Tenant awareness (adjust type if your tenants.id is not string)
                $table->string('tenant_id')->index();
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                // Optional: link to a local user/creator record
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                // Core Stripe Account identifiers
                $table->string('stripe_account_id', 191)->unique(); // acct_xxx
                $table->string('type', 32)->default('express'); // standard|express|custom|other

                // Basic account info
                $table->string('email', 191)->nullable();
                $table->string('country', 2)->nullable();

                // Capability / onboarding flags
                $table->boolean('charges_enabled')->default(false);
                $table->boolean('payouts_enabled')->default(false);
                $table->boolean('details_submitted')->default(false);

                // Status + reasons
                $table->string('status', 32)->default('pending'); // pending|active|restricted|disabled
                $table->string('disabled_reason', 191)->nullable();

                // Stripe JSON blobs for debugging/audit
                $table->json('capabilities')->nullable();
                $table->json('requirements')->nullable();
                $table->json('payouts_schedule')->nullable();
                $table->json('metadata')->nullable();
                $table->json('raw_payload')->nullable(); // last full Account object you stored

                // Onboarding timestamps
                $table->timestamp('onboarded_at')->nullable();
                $table->timestamp('last_synced_at')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
