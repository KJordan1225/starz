<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->morphs('model'); // model_type, model_id (both NOT NULL)
            // NOT NULL + default sentinel so it can be part of the PK
            $table->string('tenant_id')->default('landlord')->index();

            $table->primary(['role_id', 'model_id', 'model_type', 'tenant_id']);

            $table->foreign('role_id')
                ->references('id')->on('roles')
                ->cascadeOnDelete();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->morphs('model');
            $table->string('tenant_id')->default('landlord')->index();

            $table->primary(['permission_id', 'model_id', 'model_type', 'tenant_id']);

            $table->foreign('permission_id')
                ->references('id')->on('permissions')
                ->cascadeOnDelete();
        });
    }
    public function down(): void {
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
    }
};
