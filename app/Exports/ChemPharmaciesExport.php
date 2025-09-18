<?php

namespace App\Exports;

use App\Models\ChemPharmacy;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChemPharmaciesExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(private array $filters = []) {}

    public function query()
    {
        $q = ChemPharmacy::query()->orderBy('id');
        // applique tes filtres si tu veux (status, zone, etc.)
        return $q;
    }

    public function headings(): array
    {
        return [
            'id','status','name','phone','email','address','description',
            'logo','zone_id','rating','nb_review','supplier_id','user_id','created_at',
        ];
    }

    public function map($p): array
    {
        return [
            $p->id,
            $p->status,
            $p->name,
            $p->phone,
            $p->email,
            $p->address,
            $p->description,
            $p->logo,     // clé S3 telle quelle (pharmacies/xxx.png)
            $p->zone_id,
            $p->rating,
            $p->nb_review,
            $p->supplier_id,
            $p->user_id,
            optional($p->created_at)?->toDateTimeString(),
        ];
    }
}
