<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Adjust types/lengths if your app uses UUIDs or different PKs.
        Schema::create('role_user', function (Blueprint $table) {
            $table->bigIncrements('id');

            // FK to users & roles (assumes bigIncrements('id') on both tables)
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignId('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            // Tenant scope for the assignment (NULL = landlord/global)
            $table->string('tenant_id')->nullable()->index();

            $table->timestamps();

            // Prevent duplicate assignments within the same tenant scope
            $table->unique(
                ['user_id', 'role_id', 'tenant_id'],
                'role_user_user_role_tenant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_user');
    }
};
