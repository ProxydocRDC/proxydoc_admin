<?php

namespace App\Models;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

class ProxyPlanFeature extends Model
{
    protected $table = 'proxy_plan_features';
    protected $guarded = [];

    protected $casts = [
        'status'      => 'integer',
        'is_included' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id');
    }
}
