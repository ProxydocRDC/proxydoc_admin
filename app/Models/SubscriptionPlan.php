<?php
namespace App\Models;

use App\Models\Feature;
use App\Models\ProxyPlanFeature;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $table      = 'proxy_subscription_plans';
    protected $guarded    = [];
    protected $attributes = [
        'status' => 1,
    ];
    protected $casts = [
        'status'           => 'integer',
        'price'            => 'decimal:2',
        'extra_user_price' => 'decimal:2',
        'max_users'        => 'integer',
        'periodicity'      => 'integer', // mois
    ];

    // Helpers
    public function scopeActive($q)
    {return $q->where('status', 1);}

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class, 'plan_id');
    }

    public function planFeatures()
    {
        return $this->hasMany(ProxyPlanFeature::class, 'plan_id');
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'proxy_plan_features', 'plan_id', 'feature_id')
            ->withPivot(['is_included', 'status'])
            ->withTimestamps();
    }
}
