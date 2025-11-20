<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('subscription_id');
            $table->foreign('subscription_id')
                ->references('id')
                ->on('subscriptions')
                ->cascadeOnDelete();

            $table->string('paypal_subscription_id');
            $table->string('paypal_transaction_id')->nullable();

            $table->string('currency', 3)->default('USD');
            $table->decimal('gross_amount', 10, 2);

            $table->decimal('platform_share', 10, 2); // 20%
            $table->decimal('creator_share', 10, 2);  // 80%

            $table->string('creator_payout_batch_id')->nullable(); // from PayPal Payouts

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
