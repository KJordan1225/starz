<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {

            // Payment processor
            $table->string('provider', 20)
                ->default('stripe')
                ->after('id')
                ->comment('stripe | paypal | other');

            // Stripe identifiers
            $table->string('stripe_customer_id', 255)
                ->nullable()
                ->after('provider');

            $table->string('stripe_account_id', 255)
                ->nullable()
                ->comment('Stripe Connect account (creator)');

            $table->string('stripe_subscription_id', 255)
                ->nullable();

            $table->string('stripe_price_id', 255)
                ->nullable();

            $table->string('stripe_product_id', 255)
                ->nullable();

            // Status & lifecycle
            if (Schema::hasColumn('subscriptions', 'status')) {
                $table->dropColumn('status');
            }
            $table->string('status', 50)
                ->default('active')
                ->comment('active | trialing | canceled | incomplete | past_due');

            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();

            // Financials
            $table->integer('amount')
                ->nullable()
                ->comment('Amount in cents');

            $table->string('currency', 10)
                ->default('usd');

            // Metadata / flexibility
            $table->json('provider_payload')
                ->nullable()
                ->comment('Raw webhook or provider snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'stripe_customer_id',
                'stripe_account_id',
                'stripe_subscription_id',
                'stripe_price_id',
                'stripe_product_id',
                'status',
                'trial_ends_at',
                'current_period_start',
                'current_period_end',
                'canceled_at',
                'amount',
                'currency',
                'provider_payload',
            ]);
        });
    }
};
