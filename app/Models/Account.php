<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Account extends Model
{
    use SoftDeletes;
    use BelongsToTenant; // assumes you already have this trait for tenant_id scoping

    protected $table = 'accounts';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'stripe_account_id',
        'type',
        'email',
        'country',
        'charges_enabled',
        'payouts_enabled',
        'details_submitted',
        'status',
        'disabled_reason',
        'capabilities',
        'requirements',
        'payouts_schedule',
        'metadata',
        'raw_payload',
        'onboarded_at',
        'last_synced_at',
    ];

    protected $casts = [
        'charges_enabled'  => 'boolean',
        'payouts_enabled'  => 'boolean',
        'details_submitted'=> 'boolean',
        'capabilities'     => 'array',
        'requirements'     => 'array',
        'payouts_schedule' => 'array',
        'metadata'         => 'array',
        'raw_payload'      => 'array',
        'onboarded_at'     => 'datetime',
        'last_synced_at'   => 'datetime',
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('charges_enabled', true)
            ->where('payouts_enabled', true);
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

    public function isFullyOnboarded(): bool
    {
        return $this->status === 'active'
            && $this->charges_enabled
            && $this->payouts_enabled
            && $this->details_submitted;
    }

    public function markFromStripeAccountObject(array $account): void
    {
        // Simple mapper you can call when you fetch \Stripe\Account from API
        $this->stripe_account_id  = $account['id'] ?? $this->stripe_account_id;
        $this->type               = $account['type'] ?? $this->type;
        $this->email              = $account['email'] ?? $this->email;
        $this->country            = $account['country'] ?? $this->country;

        $this->charges_enabled    = (bool) ($account['charges_enabled'] ?? false);
        $this->payouts_enabled    = (bool) ($account['payouts_enabled'] ?? false);
        $this->details_submitted  = (bool) ($account['details_submitted'] ?? false);

        $this->capabilities       = $account['capabilities']    ?? $this->capabilities;
        $this->requirements       = $account['requirements']    ?? $this->requirements;
        $this->payouts_schedule   = $account['settings']['payouts'] ?? $this->payouts_schedule;
        $this->metadata           = $account['metadata']        ?? $this->metadata;
        $this->raw_payload        = $account;

        $this->status             = $this->isFullyOnboarded() ? 'active' : 'pending';
        $this->last_synced_at     = now();

        if ($this->isFullyOnboarded() && ! $this->onboarded_at) {
            $this->onboarded_at = now();
        }

        $this->save();
    }
}
