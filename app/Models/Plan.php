<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'plans';

    protected $fillable = [
        'tenant_id',

        'name',
        'description',

        'stripe_price_id',
        'stripe_product_id',

        'amount',
        'currency',
        'interval',

        'active',
        'featured',
        'price',  // The price for the plan

        'metadata',
    ];

    protected $casts = [
        'active'    => 'boolean',
        'featured'  => 'boolean',
        'metadata'  => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function displayPrice(): string
    {
        if (! $this->amount) {
            return '';
        }

        return sprintf(
            '%s%.2f / %s',
            strtoupper($this->currency) === 'USD' ? '$' : strtoupper($this->currency) . ' ',
            $this->amount / 100,
            $this->interval
        );
    }
}