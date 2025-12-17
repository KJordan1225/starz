<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'price')) {
                $table->decimal('price', 10, 2)
                      ->default(0.00)
                      ->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'price')) {
                $table->decimal('price', 10, 2)
                      ->default(null)
                      ->change();
            }
        });
    }
};