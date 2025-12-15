<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Subscription ledger only
                $table->string('order_type', 32)->default('subscription')->index();

                // Tenant-aware
                $table->string('tenant_id', 36)->index();
                $table->foreign('tenant_id')
                    ->references('id')->on('tenants')
                    ->cascadeOnDelete();

                // Subscriber (platform user)
                $table->unsignedBigInteger('user_id')->index();
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->cascadeOnDelete();

                // Stripe identifiers
                $table->string('stripe_session_id', 191)->nullable()->index();        // cs_...
                $table->string('stripe_subscription_id', 191)->nullable()->index();   // sub_...

                // Optional but very useful for a ledger
                $table->string('stripe_customer_id', 191)->nullable()->index();       // cus_...
                $table->string('stripe_price_id', 191)->nullable()->index();          // price_... (the plan price used)

                // Subscription lifecycle
                $table->string('status', 32)->default('created')->index();            // created|trialing|active|past_due|canceled|unpaid|incomplete|incomplete_expired
                $table->boolean('cancel_at_period_end')->default(false);
                $table->timestamp('current_period_start')->nullable();
                $table->timestamp('current_period_end')->nullable();
                $table->timestamp('canceled_at')->nullable();

                // Metadata + payloads for debugging/auditing
                $table->json('metadata')->nullable();
                $table->json('raw_payload')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'user_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};