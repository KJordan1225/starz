<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');

                // Sized to avoid oversized composite index:
                $table->string('name', 128);                    // e.g. 'admin', 'user'
                $table->string('guard_name', 32)->default('web'); // e.g. 'web'
                $table->string('scope', 16)->default('tenant');   // e.g. 'tenant'|'landlord'
                $table->string('tenant_id', 64)->nullable()->index(); // stancl id / UUID etc.

                $table->timestamps();

                // Composite uniqueness (<= 240 chars total -> ~960 bytes on utf8mb4)
                $table->unique(
                    ['tenant_id', 'name', 'guard_name', 'scope'],
                    'roles_tenant_name_guard_scope_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
