<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name','slug','guard_name','scope','tenant_id'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_has_permissions');
    }

    public function scopeForTenant($q, ?string $tenantId) {
        return $q->where('scope','tenant')->where('tenant_id',$tenantId);
    }
    public function scopeLandlord($q) {
        return $q->where('scope','landlord')->whereNull('tenant_id');
    }
}

