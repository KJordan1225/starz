<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Add tenant_id column to media table
            $table->string('tenant_id');

            // Add foreign key constraint for tenant_id
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');

            // Add index on tenant_id for optimization
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            // Drop foreign key and index before removing the column
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};

