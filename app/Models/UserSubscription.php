<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvite;
use App\Models\SubscriptionMember;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
        return $this->belongsTo(\App\Models\User::class);
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

    /** Abonnements qui expirent entre aujourd’hui et +$days (par défaut 5). */
    public function scopeExpiringWithin(Builder $q, int $days = 5): Builder
    {
        return $q->active()
            ->whereDate('end_date', '>=', now()->toDateString())
            ->whereDate('end_date', '<=', now()->addDays($days)->toDateString());
    }

    /* Accessors utiles */
    public function getDaysRemainingAttribute(): ?int
    {
        if (! $this->end_date) return null;
        // diffInDays(false) → nombre signé (négatif si déjà expiré)
        return now()->startOfDay()->diffInDays(Carbon::parse($this->end_date)->startOfDay(), false);
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        $d = $this->days_remaining;
        return $d !== null && $d >= 0 && $d <= 5;
    }
     /** Abonnements actifs qui expirent dans <= $days jours (par défaut 5) */
    public function scopeExpiringWithin2(Builder $q, int $days = 5): Builder
    {
        $from  = Carbon::now()->startOfDay();
        $to    = Carbon::now()->addDays($days)->endOfDay();

        return $q->where('subscription_status', 'active')
                 ->whereBetween('end_date', [$from, $to]);
    }



    public function scopeExpired(Builder $q): Builder
    {
        return $q->where('subscription_status', 'expired');
    }

    /** Jours restants (négatif si déjà expiré) */
    protected $appends = ['days_remaining'];

    public function getDaysRemainingAttribute2(): ?int
    {
        if (! $this->end_date) return null;

        return Carbon::now()->startOfDay()
            ->diffInDays(Carbon::parse($this->end_date)->startOfDay(), false);
    }
     /** Abonnements actifs qui expirent dans <= $days jours */

}
