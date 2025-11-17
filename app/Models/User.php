<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant; // << key trait

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    // If you’re using stancl/tenancy’s trait, keep it:
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id', // if you keep a default/home tenant on the user
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Tenant-aware many-to-many roles relationship via role_user pivot.
     * Pivot holds tenant_id to scope a role assignment to a tenant (or NULL for landlord).
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user', 'user_id', 'role_id')
            ->withPivot(['tenant_id'])
            ->withTimestamps();
    }


    /**
     * Assign a role by Role model or role name.
     * Example: $user->assignRole('admin', $tenantId);
     */
    public function assignRole(Role|string $role, ?string $tenantId = null): static
    {
        $roleId = $role instanceof Role
            ? $role->id
            : Role::query()
                ->where('name', $role)
                ->where('tenant_id', $tenantId) // landlord/global = null
                ->value('id');

        if (! $roleId) {
            throw new \InvalidArgumentException('Role not found for the given tenant scope.');
        }

        $this->roles()->syncWithoutDetaching([
            $roleId => ['tenant_id' => $tenantId],
        ]);

        return $this;
    }

    /**
     * Check if the user has a role (optionally scoped to a tenant).
     * Example: $user->hasRole('admin', $tenantId)
     */
    public function hasRole(Role|string $role, ?string $tenantId = null): bool
    {
        
        if ($role instanceof Role) {
            $tenantId = $tenantId ?? $role->tenant_id;
            $role = $role->name;
        }

        $hasRole = $this->roles()
            ->where('roles.name', $role)
            ->when($tenantId !== null, fn ($q) => $q->where('role_user.tenant_id', $tenantId))  // Correct pivot column reference
            ->when($tenantId === null, fn ($q) => $q->whereNull('role_user.tenant_id'))  // Correct pivot column reference
            ->exists();        
        
        return $hasRole;

    }

    /**
     * Get all roles for a given tenant (or landlord/global when null).
     */
    public function rolesForTenant(?string $tenantId = null)
    {
        return $this->roles()
            ->when($tenantId !== null, fn ($q) => $q->wherePivot('tenant_id', $tenantId))
            ->when($tenantId === null, fn ($q) => $q->whereNull('role_user.tenant_id'))
            ->get();
    }

    /**
     * Remove a role from the user (respecting tenant scope).
     */
    public function removeRole(Role|string $role, ?string $tenantId = null): void
    {
        $roleId = $role instanceof Role
            ? $role->id
            : Role::query()
                ->where('name', $role)
                ->where('tenant_id', $tenantId)
                ->value('id');

        if ($roleId) {
            // Delete only matching pivot row (respect tenant scope)
            $this->roles()
                ->newPivotStatement()
                ->where('user_id', $this->getKey())
                ->where('role_id', $roleId)
                ->when($tenantId !== null, fn ($q) => $q->where('tenant_id', $tenantId))
                ->when($tenantId === null, fn ($q) => $q->whereNull('tenant_id'))
                ->delete();
        }
    }

    /**
     * Optional: clean up pivot rows when deleting a user.
     */
    protected static function booted(): void
    {
        static::deleting(function (self $user) {
            $user->roles()->detach();
        });
    }

}
