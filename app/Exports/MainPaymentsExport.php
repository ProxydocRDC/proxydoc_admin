<?php

namespace App\Exports;

use App\Models\MainPayment;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MainPaymentsExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private ?string $from = null,
        private ?string $to = null,
        private ?int $status = null,
    ) {}

    public function query()
    {
        $q = MainPayment::query()->orderBy('id', 'desc');
        if ($this->from) $q->whereDate('created_at', '>=', $this->from);
        if ($this->to)   $q->whereDate('created_at', '<=', $this->to);
        if ($this->status) $q->where('payment_status', $this->status);

        return $q;
    }

    public function headings(): array
    {
        return [
            'id','date','method','channel','currency','amount','total_amount',
            'status','telephone','gateway','order_number','reference',
            'provider_reference','order_id','subscription_id','appointment_id','created_by',
        ];
    }

    public function map($m): array
    {
        return [
            $m->id,
            optional($m->created_at)?->format('Y-m-d H:i:s'),
            $m->method,
            $m->channel,
            $m->currency,
            $m->amount,
            $m->total_amount,
            $m->payment_status_label,
            $m->telephone,
            $m->gateway,
            $m->order_number,
            $m->reference,
            $m->provider_reference,
            $m->order_id,
            $m->subscription_id,
            $m->appointment_id,
            $m->created_by,
        ];
    }
}
