<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'paypal_subscription_id',
        'paypal_transaction_id',
        'currency',
        'gross_amount',
        'platform_share',
        'creator_share',
        'creator_payout_batch_id',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
