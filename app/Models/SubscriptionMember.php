<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionMember extends Model
{
    protected $table = 'proxy_subscription_members';
    protected $guarded = [];

    protected $casts = [
        'status'       => 'integer',
        'seat_count'   => 'integer',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
        'invited_at'   => 'datetime',
        'accepted_at'  => 'datetime',
    ];
protected $attributes = [
        'status'       => 1,
    ];
    public function subscription()
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function patient()
    {
        return $this->belongsTo(\App\Models\ProxyPatient::class, 'patient_id'); // si modèle dispo
    }
}
