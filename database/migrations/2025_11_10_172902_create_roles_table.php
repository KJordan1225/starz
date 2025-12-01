<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->bigIncrements('id');

                $table->string('name', 128);
                $table->string('guard_name', 32)->default('web');
                $table->string('scope', 16)->default('tenant');
                $table->string('tenant_id', 64)->nullable()->index();

                $table->timestamps();

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
}
