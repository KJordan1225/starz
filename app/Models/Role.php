<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class Role extends Model
{
    use HasFactory, BelongsToTenant;

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'name',
        'guard_name',
        'scope',
        'tenant_id',
    ];

    /**
     * Default attribute values.
     */
    protected $attributes = [
        'guard_name' => 'web',
        'scope'      => 'tenant',
    ];

    /**
     * A role may belong to a tenant (or landlord if tenant_id = null/'landlord').
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id', 'id');
    }

    /**
     * Users that have been assigned this role.
     *
     * Compatible with your `model_has_roles` pivot (morph style).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'role_user', 'role_id', 'user_id')
            ->withPivot(['tenant_id'])
            ->withTimestamps();
    }


    /**
     * Permissions attached to this role (if you’re using model_has_permissions).
     */
    public function permissions(): MorphToMany
    {
        return $this->morphToMany(
            Permission::class,
            'model',
            'model_has_permissions',
            'model_id',   // FK to roles
            'permission_id'
        )
        ->withPivot('tenant_id')
        ->withTimestamps();
    }
}
