<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->index();
            $table->string('guard_name')->default('web');
            $table->enum('scope', ['landlord','tenant'])->default('tenant');
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();

            $table->unique(['slug','guard_name','scope','tenant_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('permissions'); }
};
