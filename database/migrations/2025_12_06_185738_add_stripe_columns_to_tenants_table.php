<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {

            // Drop column if it already exists
            if (Schema::hasColumn('tenants', 'stripe_account_id')) {
                $table->dropColumn('stripe_account_id');
            }

            if (Schema::hasColumn('tenants', 'stripe_onboarded_at')) {
                $table->dropColumn('stripe_onboarded_at');
            }

        });

        Schema::table('tenants', function (Blueprint $table) {
            // Re-add columns with correct types + indexing
            $table->string('stripe_account_id')->nullable()->index();
            $table->timestamp('stripe_onboarded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'stripe_account_id')) {
                $table->dropIndex(['stripe_account_id']);
                $table->dropColumn('stripe_account_id');
            }

            if (Schema::hasColumn('tenants', 'stripe_onboarded_at')) {
                $table->dropColumn('stripe_onboarded_at');
            }
        });
    }
};

