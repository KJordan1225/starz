<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1) tenant_id column (nullable for landlord users)
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->string('tenant_id')->nullable()->after('id')->index();
            }

            // 2) Drop the old global unique on email if it exists
            //    (Laravel 11: use native index listing)
            $indexes = Schema::getIndexListing('users'); // array of index names
            if (in_array('users_email_unique', $indexes, true)) {
                $table->dropUnique('users_email_unique');
            } else {
                // Fallback: if the index name differs but was created by Laravel defaults,
                // try dropping by columns (derives the name automatically)
                // This is safe to call if no such index exists.
                try { $table->dropUnique(['email']); } catch (\Throwable $e) {}
            }

            // 3) Add per-tenant unique(email) if it isn't there yet
            if (! in_array('users_tenant_email_unique', $indexes, true)) {
                $table->unique(['tenant_id', 'email'], 'users_tenant_email_unique');
            }

            // 4) Optional FK to tenants.id (stancl default is string PK)
            //    Only add if not already present
            $fks = method_exists(Schema::class, 'getForeignKeys')
                ? Schema::getForeignKeys('users')   // Laravel 11 provides metadata
                : [];

            $hasTenantFk = collect($fks)->contains(fn ($fk) => $fk['name'] ?? '' === 'users_tenant_id_foreign');

            if (! $hasTenantFk) {
                $table->foreign('tenant_id')
                      ->references('id')->on('tenants')
                      ->nullOnDelete(); // on tenant delete, null the tenant_id
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop composite unique (if present)
            try { $table->dropUnique('users_tenant_email_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique(['tenant_id', 'email']); } catch (\Throwable $e) {}

            // Restore global unique on email
            try { $table->unique('email', 'users_email_unique'); } catch (\Throwable $e) {}

            // Drop FK then column
            try { $table->dropForeign('users_tenant_id_foreign'); } catch (\Throwable $e) {}
            if (Schema::hasColumn('users', 'tenant_id')) {
                $table->dropColumn('tenant_id');
            }
        });
    }
};
