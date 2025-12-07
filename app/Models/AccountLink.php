<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccountLink extends Model
{
    use SoftDeletes;
    use BelongsToTenant; // assumes you already have this trait for tenant_id scoping

    protected $table = 'account_links';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'stripe_account_id',
        'stripe_account',
        'stripe_link_id',
        'url',
        'type',
        'return_url',
        'refresh_url',
        'expires_at',
        'used_at',
        'metadata',
        'raw_payload',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
        'metadata'   => 'array',
        'raw_payload'=> 'array',
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

    public function stripeAccount()
    {
        return $this->belongsTo(StripeAccount::class, 'stripe_account_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
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

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function markUsed(): void
    {
        $this->used_at = now();
        $this->save();
    }

    /**
     * Hydrate from a Stripe\AccountLink object (or array).
     */
    public function fillFromStripe(array $link): void
    {
        $this->stripe_link_id = $link['id'] ?? $this->stripe_link_id;
        $this->url            = $link['url'] ?? $this->url;
        $this->type           = $link['type'] ?? $this->type;
        $this->return_url     = $link['return_url'] ?? $this->return_url;
        $this->refresh_url    = $link['refresh_url'] ?? $this->refresh_url;

        if (isset($link['expires_at'])) {
            $this->expires_at = \Carbon\Carbon::createFromTimestamp($link['expires_at']);
        }

        $this->raw_payload = $link;
        $this->save();
    }
}
