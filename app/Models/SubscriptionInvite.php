<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionInvite extends Model
{
    protected $table = 'proxy_subscription_invites';
    protected $guarded = [];

    protected $casts = [
        'status'      => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];
protected $attributes = [
        'status'       => 1,
    ];
    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function invitedUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }
}
