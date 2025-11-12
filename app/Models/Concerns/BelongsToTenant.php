<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    /**
     * Override in your model if you use a different column name.
     */
    protected static function tenantColumn(): string
    {
        return 'tenant_id';
    }

    /**
     * Set to true in a model to disable the global tenant scope for that model only.
     * Example in a model:
     *   protected bool $disableTenantScope = true;
     */
    protected bool $disableTenantScope = false;

    /**
     * Boot the trait: add global scope + auto-fill tenant_id on create.
     */
    protected static function bootBelongsToTenant(): void
    {
        // Global scope (only when tenancy is initialized and scope not disabled)
        static::addGlobalScope('tenant', function (Builder $query) {
            /** @var Model&self $model */
            $model = $query->getModel();

            if ($model->shouldApplyTenantScope()) {
                $column = $model::tenantColumn();
                $tenantId = static::resolveTenantId();

                // If we have an active tenant, constrain to it
                if ($tenantId !== null) {
                    $query->where($model->getTable() . '.' . $column, $tenantId);
                }
            }
        });

        // Auto-populate tenant_id on create if empty and a tenant is active
        static::creating(function (Model $model) {
            /** @var Model&self $model */
            $column = $model::tenantColumn();

            if (blank($model->{$column})) {
                $tenantId = static::resolveTenantId();
                if ($tenantId !== null) {
                    $model->{$column} = $tenantId;
                }
            }
        });
    }

    /**
     * Relationship to the Tenant model.
     * Adjust the class if your Tenant model lives elsewhere.
     */
    public function tenant(): BelongsTo
    {
        $column = static::tenantColumn();
        return $this->belongsTo(\App\Models\Tenant::class, $column, 'id');
    }

    /**
     * Scope: include landlord/global rows (tenant_id = NULL) in addition to current tenant.
     * Use when you want "fallback" behavior (e.g., settings).
     */
    public function scopeWithLandlord(Builder $query): Builder
    {
        $column   = static::tenantColumn();
        $tenantId = static::resolveTenantId();

        if ($tenantId === null) {
            // No active tenant -> landlord only
            return $query->whereNull($this->getTable() . '.' . $column);
        }

        return $query->withoutGlobalScope('tenant')
            ->where(function (Builder $q) use ($column, $tenantId) {
                $q->where($this->getTable() . '.' . $column, $tenantId)
                  ->orWhereNull($this->getTable() . '.' . $column);
            });
    }

    /**
     * Scope: only landlord/global rows (tenant_id = NULL).
     */
    public function scopeLandlordOnly(Builder $query): Builder
    {
        $column = static::tenantColumn();
        return $query->withoutGlobalScope('tenant')
                     ->whereNull($this->getTable() . '.' . $column);
    }

    /**
     * Scope: only current-tenant rows (ignores landlord).
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $column   = static::tenantColumn();
        $tenantId = static::resolveTenantId();

        return $query->withoutGlobalScope('tenant')
                     ->when($tenantId !== null,
                        fn ($q) => $q->where($this->getTable() . '.' . $column, $tenantId),
                        fn ($q) => $q->whereNull($this->getTable() . '.' . $column) // no tenant active
                     );
    }

    /**
     * Scope: all tenants (remove the trait's global scope).
     */
    public function scopeAllTenants(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    /**
     * Helper: get the resolved tenant id (string|int) or null if none.
     */
    public static function resolveTenantId(): ?string
    {
        // stancl/tenancy helpers if present
        if (function_exists('tenancy') && function_exists('tenant')) {
            try {
                if (tenancy()->initialized) {
                    $id = tenant('id'); // often string
                    return $id !== null ? (string) $id : null;
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        // Try to pull from current request route param '{tenant}' (path-based tenancy)
        try {
            $req = request();
            if ($req && $req->route()) {
                $param = $req->route('tenant');
                if (!is_null($param) && $param !== '') {
                    return (string) $param;
                }
            }
        } catch (\Throwable) {
            // ignore
        }

        return null; // landlord/global
    }

    /**
     * Whether to apply the global tenant scope.
     */
    protected function shouldApplyTenantScope(): bool
    {
        if (property_exists($this, 'disableTenantScope') && $this->disableTenantScope === true) {
            return false;
        }

        // Apply only when tenancy is initialized (so artisans/seeding without tenant won't break)
        if (function_exists('tenancy')) {
            try {
                return tenancy()->initialized === true;
            } catch (\Throwable) {
                return false;
            }
        }

        return false;
    }

    /**
     * Convenience: is this model row landlord/global?
     */
    public function isLandlordRow(): bool
    {
        $column = static::tenantColumn();
        return blank($this->{$column});
    }

    /**
     * Convenience: force-set the tenant id on a model instance.
     */
    public function setTenantId(?string $tenantId): static
    {
        $column = static::tenantColumn();
        $this->{$column} = $tenantId;

        return $this;
    }

    /**
     * Accessor: get current (resolved) tenant id quickly.
     */
    public static function currentTenantId(): ?string
    {
        return static::resolveTenantId();
    }
}
