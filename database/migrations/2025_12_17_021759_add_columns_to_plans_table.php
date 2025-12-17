<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'name')) {
                $table->string('name', 255)
                    ->default('Monthly Subscription')
                    ->after('tenant_id');
            }
			
			if (! Schema::hasColumn('plans', 'description')) {
                $table->string('description', 255)
                    ->default('Monthly Subscription Description')
                    ->after('tenant_id');
            }
			
			if (! Schema::hasColumn('plans', 'amount')) {
                $table->bigInteger('amount');
            }
			if (! Schema::hasColumn('plans', 'active')) {
                $table->boolean('active');
            }
			
			if (! Schema::hasColumn('plans', 'interval')) {
                $table->string('interval');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'name')) {
                $table->dropColumn('name');
            }
			
			if (Schema::hasColumn('plans', 'description')) {
                $table->dropColumn('description');
            }
			
			if (Schema::hasColumn('plans', 'amount')) {
                $table->dropColumn('amount');
            }
			if (Schema::hasColumn('plans', 'active')) {
                $table->dropColumn('active');
            }
			
			if (Schema::hasColumn('plans', 'interval')) {
                $table->dropColumn('interval');
            }
        });
    }
};