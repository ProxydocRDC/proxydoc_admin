<?php

namespace App\Models;

use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvite;
use App\Models\SubscriptionMember;
use Illuminate\Database\Eloquent\Model;

class UserSubscription extends Model
{
    protected $table = 'proxy_user_subscriptions';
    protected $guarded = [];

    protected $casts = [
        'status'               => 'integer',
        'seats'                => 'integer',
        'start_date'           => 'date',
        'end_date'             => 'date',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
        'subscription_status'  => 'string', // enum texte
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invites()
    {
        return $this->hasMany(SubscriptionInvite::class, 'subscription_id');
    }

    public function members()
    {
        return $this->hasMany(SubscriptionMember::class, 'subscription_id');
    }

    public function scopeActive($q)
    {
        return $q->where('subscription_status', 'active')
                 ->whereDate('end_date', '>=', now()->toDateString());
    }

    public function getIsExpiredAttribute(): bool
    {
        return (string) $this->subscription_status === 'expired'
            || ($this->end_date && $this->end_date->isPast());
    }
}
