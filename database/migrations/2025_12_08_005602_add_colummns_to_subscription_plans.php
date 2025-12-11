<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('subscription_plans', 'amount')) {
                $table->bigInteger('amount');
            }
			if (! Schema::hasColumn('subscription_plans', 'active')) {
                $table->boolean('active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            if (Schema::hasColumn('subscription_plans', 'amount')) {
                $table->dropColumn('amount');
            }
			if (Schema::hasColumn('subscription_plans', 'active')) {
                $table->dropColumn('active');
            }
        });
    }
};