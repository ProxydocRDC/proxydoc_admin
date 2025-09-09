<?php
namespace App\Models;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    protected $table      = 'proxy_features';
    protected $guarded    = [];
    protected $attributes = [
        'status' => 1,
    ];
    protected $casts = [
        'status'     => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeActive($q)
    {return $q->where('status', 1);}
    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'proxy_plan_features', 'feature_id', 'plan_id')
            ->withPivot(['is_included', 'status'])
            ->withTimestamps();
    }

}
