<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tenant_images', function (Blueprint $table) {
            $table->id();

            // 1. title column - nullable string
            $table->string('title')->nullable();

            // 2. description column - nullable string
            $table->string('description')->nullable();

            // 3. tenant_id column - nullable string, FK to tenants.id
            $table->string('tenant_id')->nullable();

            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            $table->index('tenant_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_images');
    }
};