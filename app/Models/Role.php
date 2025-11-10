<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name','slug','guard_name','scope','tenant_id'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    /** Scope helpers */
    public function scopeForTenant($q, ?string $tenantId) {
        return $q->where('scope','tenant')->where('tenant_id',$tenantId);
    }
    public function scopeLandlord($q) {
        return $q->where('scope','landlord')->whereNull('tenant_id');
    }    
    
    public function users()
    {
        return $this->belongsToMany(\App\Models\User::class)
                    ->withPivot('tenant_id')
                    ->withTimestamps();
    }


}

