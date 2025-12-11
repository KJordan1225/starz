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
                $table->id();

                // Optional: per-tenant plans
                $table->string('tenant_id')->nullable()->index();
                $table->foreign('tenant_id')
                    ->references('id')->on('tenants')
                    ->cascadeOnDelete();

                $table->string('name');
                $table->unsignedBigInteger('amount'); // cents
                $table->string('currency', 3)->default('usd');
                $table->string('interval', 32)->default('month'); // month, year, etc.

                $table->string('stripe_price_id', 191)->nullable()->index();

                $table->boolean('active')->default(true);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};