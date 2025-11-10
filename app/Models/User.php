<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant; // << key trait

class User extends Authenticatable
{
    use Notifiable;
    use BelongsToTenant;

    /**
     * If your tenant column name differs, uncomment and adjust.
     */
    // public function getTenantIdColumn(): string
    // {
    //     return 'tenant_id';
    // }

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id', // allow mass-assign if you set manually (usually auto-set by tenancy)
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Optional convenience relation.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id', 'id');
    }    

    public function roles()
    {
        return $this->belongsToMany(\App\Models\Role::class)
                    ->withPivot('tenant_id')
                    ->withTimestamps();
    }

}
