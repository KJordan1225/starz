<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'subscription_plan_id',
        'paypal_subscription_id',
        'status',
        'starts_at',
        'ends_at',
        'cancel_at_period_end',
        'canceled_at',
    ];

    protected $casts = [
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'canceled_at'          => 'datetime',
        'cancel_at_period_end' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function subscriber()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Active from your app’s POV:
     * - status is "active"
     * - and ends_at is null or in the future
     */
    public function isActive(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // If no ends_at set, treat as active
        if (is_null($this->ends_at)) {
            return true;
        }

        // Active only if we haven't passed the end date yet
        return $this->ends_at->isFuture();
    }

    public function isPendingCancel(): bool
    {
        return $this->isActive() && $this->cancel_at_period_end === true;
    }

    
}
