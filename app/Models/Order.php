<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    use BelongsToTenant; // your tenant-aware trait (global scope + auto-fill tenant_id)

    protected $table = 'orders';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'stripe_order_id',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_session_id',
        'amount',
        'currency',
        'status',
        'orderable_type',
        'orderable_id',
        'metadata',
        'raw_payload',
        'paid_at',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'raw_payload' => 'array',
        'paid_at'     => 'datetime',
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

    // Polymorphic link to “what” the order is for: subscription, tip, PPV, etc.
    public function orderable()
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes / Helpers
    |--------------------------------------------------------------------------
    */

    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function isPaid(): bool
    {
        return $this->status === 'succeeded';
    }

    public function markAsPaid(?string $statusFromStripe = 'succeeded'): void
    {
        $this->status  = $statusFromStripe ?: 'succeeded';
        $this->paid_at = now();
        $this->save();
    }
}