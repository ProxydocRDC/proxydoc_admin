<?php

namespace App\Models;

use App\Models\ChemOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainPayment extends Model
{
        /** @use HasFactory<\Database\Factories\MainPaymentFactory> */
    use SoftDeletes, HasFactory;

    protected $table   = 'main_payments';
    protected $guarded = [];

    protected $casts = [
        'status'       => 'integer',
        'payment_status' => 'integer', // 1 en cours, 2 approuvé, 3 rejeté
        'amount'       => 'decimal:3',
        'total_amount' => 'decimal:3',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    // Constantes utiles
    public const METHOD_MOBILE_MONEY = 'mobile_money';
    public const METHOD_CARD         = 'card';

    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_REJECTED = 3;

    /* ------------ Relations (adapte les classes si besoin) ------------ */
    public function creator()     { return $this->belongsTo(User::class, 'created_by'); }
    public function updater()     { return $this->belongsTo(User::class, 'updated_by'); }
    public function order()       { return $this->belongsTo(ChemOrder::class, 'order_id'); }
    public function subscription(){ return $this->belongsTo(MainSubscription::class, 'subscription_id'); }
    public function appointment() { return $this->belongsTo(ProxyAppointment::class, 'appointment_id'); }

    /* ------------ Accessors pratiques ------------ */
    public function getPaymentStatusLabelAttribute(): string
    {
        return match ($this->payment_status) {
            self::STATUS_APPROVED => 'Approuvé',
            self::STATUS_REJECTED => 'Rejeté',
            default               => 'En cours',
        };
    }

    public function getPaymentStatusColorAttribute(): string
    {
        return match ($this->payment_status) {
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default               => 'warning',
        };
    }

    public function scopeBetween($q, ?string $from, ?string $to)
    {
        if ($from) $q->where('created_at', '>=', $from . ' 00:00:00');
        if ($to)   $q->where('created_at', '<=', $to   . ' 23:59:59');
        return $q;
    }
}
