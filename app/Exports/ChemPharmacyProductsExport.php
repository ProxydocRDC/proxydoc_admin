<?php

namespace App\Exports;

use App\Models\ChemPharmacyProduct;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChemPharmacyProductsExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return ChemPharmacyProduct::query()->with(['pharmacy','product','manufacturer'])->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'id','status',
            'pharmacy_id','pharmacy_name',
            'product_id','product_name','product_sku',
            'manufacturer_id','manufacturer_name',
            'lot_ref','origin_country','expiry_date',
            'cost_price','sale_price','currency',
            'stock_qty','reorder_level','image','description','created_at',
        ];
    }

    public function map($m): array
    {
        return [
            $m->id, $m->status,
            $m->pharmacy_id, optional($m->pharmacy)->name,
            $m->product_id, optional($m->product)->name, optional($m->product)->sku,
            $m->manufacturer_id, optional($m->manufacturer)->name,
            $m->lot_ref, $m->origin_country, optional($m->expiry_date)?->format('Y-m-d'),
            $m->cost_price, $m->sale_price, $m->currency,
            $m->stock_qty, $m->reorder_level, $m->image, $m->description,
            optional($m->created_at)?->toDateTimeString(),
        ];
    }
}
