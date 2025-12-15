<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'orders';

    protected $fillable = [
        'order_type',
        'tenant_id',
        'user_id',

        'stripe_session_id',
        'stripe_subscription_id',
        'stripe_customer_id',
        'stripe_price_id',

        'status',
        'cancel_at_period_end',
        'current_period_start',
        'current_period_end',
        'canceled_at',

        'metadata',
        'raw_payload',
    ];

    protected $casts = [
        'metadata'             => 'array',
        'raw_payload'          => 'array',

        'cancel_at_period_end' => 'boolean',

        'current_period_start' => 'datetime',
        'current_period_end'   => 'datetime',
        'canceled_at'          => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing'], true);
    }

    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }
}