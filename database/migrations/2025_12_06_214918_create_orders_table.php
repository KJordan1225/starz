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

                // Tenant-aware
                $table->string('tenant_id')->index();
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                // Buyer (platform user)
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->cascadeOnDelete();

                // Core Stripe identifiers (adjust to match your flow)
                $table->string('stripe_order_id', 191)->nullable()->unique();   // custom “order” id if you use one
                $table->string('stripe_payment_intent_id', 191)->nullable()->index();
                $table->string('stripe_charge_id', 191)->nullable()->index();
                $table->string('stripe_session_id', 191)->nullable()->index();  // Checkout Session

                // Money
                // amount in the smallest currency unit (e.g. cents)
                $table->unsignedBigInteger('amount')->default(0);
                $table->string('currency', 3)->default('usd');

                // Simple status tracking
                $table->enum('status', [
                    'created',
                    'requires_payment_method',
                    'requires_action',
                    'processing',
                    'succeeded',
                    'canceled',
                    'failed',
                ])->default('created');

                // Optional references – e.g. what this order is for
                $table->string('orderable_type')->nullable();
                $table->unsignedBigInteger('orderable_id')->nullable()->index();

                // JSON blobs for debugging / auditing
                $table->json('metadata')->nullable();
                $table->json('raw_payload')->nullable(); // last Stripe payload you stored

                $table->timestamp('paid_at')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};