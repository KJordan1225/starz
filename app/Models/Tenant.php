<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
// Note: We remove HasDatabase, HasDomains (if not using domains) & TenantWithDatabase

class Tenant extends BaseTenant
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The data key will contain all the extra data you need.
     */
    protected $casts = [
        'data' => 'array',
    ];
}