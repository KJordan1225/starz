<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_links')) {
            Schema::create('account_links', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Tenant-aware (match type to tenants.id)
                $table->string('tenant_id')->index();
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                // Optional: which local user initiated this link
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                // Local FK to stripe_accounts row (optional but nice)
                $table->unsignedBigInteger('stripe_account_id')->nullable()->index();
                $table->foreign('stripe_account_id')
                    ->references('id')
                    ->on('accounts')
                    ->cascadeOnDelete();

                // Stripe identifiers
                $table->string('stripe_account', 191)->nullable()->index(); // acct_xxx
                $table->string('stripe_link_id', 191)->nullable()->unique(); // link_xxx

                // Core link fields
                $table->string('url', 2048);
                $table->string('type', 64)->nullable(); // account_onboarding | account_update | ...

                $table->string('return_url', 2048)->nullable();
                $table->string('refresh_url', 2048)->nullable();

                // Expiry from Stripe (epoch → timestamp)
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('used_at')->nullable();

                // Stripe JSON blobs
                $table->json('metadata')->nullable();
                $table->json('raw_payload')->nullable(); // full AccountLink object

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'stripe_account_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('account_links');
    }
};
