<?php

namespace App\Models\Concerns;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Facades\Cache;

trait HasRolesAndPermissions
{
    /** ----- Relations ----- */
    public function roles(?string $tenantId = null): MorphToMany
    {
        $relation = $this->morphToMany(Role::class, 'model', 'model_has_roles');
        return $tenantId === null
            ? $relation->whereNull('model_has_roles.tenant_id')                // landlord
            : $relation->where('model_has_roles.tenant_id', $tenantId);       // tenant
    }

    public function permissions(?string $tenantId = null): MorphToMany
    {
        $relation = $this->morphToMany(Permission::class, 'model', 'model_has_permissions');
        return $tenantId === null
            ? $relation->whereNull('model_has_permissions.tenant_id')
            : $relation->where('model_has_permissions.tenant_id', $tenantId);
    }

    /** ----- Cache keys ----- */
    protected function rpCacheKey(?string $tenantId, string $type): string
    {
        $t = $tenantId ?: 'landlord';
        return "rp:{$type}:user:{$this->getMorphClass()}:{$this->getKey()}:{$t}";
    }

    protected function forgetCached(?string $tenantId = null): void
    {
        Cache::forget($this->rpCacheKey($tenantId, 'roles'));
        Cache::forget($this->rpCacheKey($tenantId, 'perms'));
    }

    /** ----- Helpers ----- */
    protected function currentTenantId(): ?string
    {
        // Works with stancl/tenancy; returns null if not initialized (landlord)
        return function_exists('tenant') && tenant() ? (string) tenant('id') : null;
    }

    /** ----- Retrieval (cached) ----- */
    public function getRoleSlugs(?string $tenantId = null): Collection
    {
        $tenantId ??= $this->currentTenantId();
        return Cache::rememberForever($this->rpCacheKey($tenantId,'roles'), function () use ($tenantId) {
            return $this->roles($tenantId)->pluck('slug')->values();
        });
    }

    public function getPermissionSlugs(?string $tenantId = null): Collection
    {
        $tenantId ??= $this->currentTenantId();
        return Cache::rememberForever($this->rpCacheKey($tenantId,'perms'), function () use ($tenantId) {
            $rolePerms = Permission::query()
                ->when(is_null($tenantId), fn($q) => $q->landlord(), fn($q) => $q->forTenant($tenantId))
                ->whereIn('id', function ($q) use ($tenantId) {
                    $q->from('role_has_permissions')
                      ->select('permission_id')
                      ->whereIn('role_id', $this->roles($tenantId)->select('roles.id'));
                })
                ->pluck('slug');

            $directPerms = $this->permissions($tenantId)->pluck('slug');

            return $rolePerms->merge($directPerms)->unique()->values();
        });
    }

    /** ----- Checks (Spatie-like) ----- */
    public function hasRole(string|array $roles, ?string $tenantId = null): bool
    {
        $tenantId ??= $this->currentTenantId();
        $roles = Arr::wrap($roles);
        $have = $this->getRoleSlugs($tenantId);
        return collect($roles)->some(fn($r) => $have->contains($this->normalize($r)));
    }

    public function hasAnyRole(string|array $roles, ?string $tenantId = null): bool
    {
        return $this->hasRole($roles, $tenantId);
    }

    public function hasAllRoles(string|array $roles, ?string $tenantId = null): bool
    {
        $tenantId ??= $this->currentTenantId();
        $roles = Arr::wrap($roles);
        $have = $this->getRoleSlugs($tenantId);
        return collect($roles)->every(fn($r) => $have->contains($this->normalize($r)));
    }

    public function hasPermissionTo(string $permission, ?string $tenantId = null): bool
    {
        return $this->can($permission, $tenantId);
    }

    public function can($ability, ?string $tenantId = null): bool
    {
        $tenantId ??= $this->currentTenantId();
        $perm = $this->normalize($ability);
        // "super-admin" short-circuit per scope
        if ($this->hasRole('super-admin', $tenantId)) return true;

        return $this->getPermissionSlugs($tenantId)->contains($perm);
    }

    /** ----- Mutators (Spatie-like) ----- */
    public function assignRole(string|array $roles, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $roles = $this->findRolesBySlug($roles, $tenantId);
        $this->roles($tenantId)->syncWithoutDetaching($roles->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    public function removeRole(string|array $roles, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $roles = $this->findRolesBySlug($roles, $tenantId);
        $this->roles($tenantId)->detach($roles->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    public function syncRoles(string|array $roles, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $roles = $this->findRolesBySlug($roles, $tenantId);
        $this->roles($tenantId)->sync($roles->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    public function givePermissionTo(string|array $permissions, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $perms = $this->findPermsBySlug($permissions, $tenantId);
        $this->permissions($tenantId)->syncWithoutDetaching($perms->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    public function revokePermissionTo(string|array $permissions, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $perms = $this->findPermsBySlug($permissions, $tenantId);
        $this->permissions($tenantId)->detach($perms->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    public function syncPermissions(string|array $permissions, ?string $tenantId = null): static
    {
        $tenantId ??= $this->currentTenantId();
        $perms = $this->findPermsBySlug($permissions, $tenantId);
        $this->permissions($tenantId)->sync($perms->pluck('id')->all());
        $this->forgetCached($tenantId);
        return $this;
    }

    /** ----- Finders ----- */
    protected function findRolesBySlug(string|array $roles, ?string $tenantId): Collection
    {
        $slugs = collect(Arr::wrap($roles))->map(fn($r) => $this->normalize($r))->values();
        return Role::query()
            ->whereIn('slug', $slugs)
            ->when(is_null($tenantId), fn($q) => $q->landlord(), fn($q) => $q->forTenant($tenantId))
            ->get();
    }

    protected function findPermsBySlug(string|array $perms, ?string $tenantId): Collection
    {
        $slugs = collect(Arr::wrap($perms))->map(fn($p) => $this->normalize($p))->values();
        return Permission::query()
            ->whereIn('slug', $slugs)
            ->when(is_null($tenantId), fn($q) => $q->landlord(), fn($q) => $q->forTenant($tenantId))
            ->get();
    }

    protected function normalize(string $value): string
    {
        return str($value)->lower()->slug('_'); // e.g., "Edit Posts" => "edit_posts"
    }
}
