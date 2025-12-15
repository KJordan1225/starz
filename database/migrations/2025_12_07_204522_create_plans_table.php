<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plans')) {
            Schema::create('plans', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Tenant scope
                $table->string('tenant_id', 36)->index();
                $table->foreign('tenant_id')
                    ->references('id')
                    ->on('tenants')
                    ->cascadeOnDelete();

                // Plan display info
                $table->string('name');
                $table->text('description')->nullable();

                // Stripe identifiers
                $table->string('stripe_price_id', 191)->unique(); // price_...
                $table->string('stripe_product_id', 191)->nullable(); // prod_...

                // Optional pricing metadata (for UI only)
                $table->unsignedBigInteger('amount')->nullable(); // cents
                $table->string('currency', 3)->default('usd');
                $table->string('interval', 16)->default('month'); // month|year

                // Flags
                $table->boolean('active')->default(true);
                $table->boolean('featured')->default(false);

                // Extra data
                $table->json('metadata')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};