<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 132);
            $table->string('guard_name', 16)->default('web');
            $table->enum('scope', ['landlord','tenant'])->default('tenant');
            $table->string('tenant_id', 132)->nullable()->index(); // null => landlord-level
            $table->timestamps();

            $table->unique(['name','guard_name','scope','tenant_id']); // prevent dupes per scope
        });
    }
    public function down(): void { Schema::dropIfExists('roles'); }
};
