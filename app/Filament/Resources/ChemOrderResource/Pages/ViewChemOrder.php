<?php

namespace App\Filament\Resources\ChemOrderResource\Pages;

use App\Filament\Resources\ChemOrderResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;

class ViewChemOrder extends ViewRecord
{
    protected static string $resource = ChemOrderResource::class;

    // (facultatif) résumé en haut
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Résumé de la commande')->schema([
                TextEntry::make('reference')->label('N°'),
                TextEntry::make('customer.name')->label('Client'),
                TextEntry::make('status')->label('Statut')
                    ->formatStateUsing(fn ($s) => [
                        'draft'=>'Brouillon','confirmed'=>'Confirmée','shipped'=>'Expédiée',
                        'completed'=>'Terminée','canceled'=>'Annulée',
                    ][$s] ?? $s),
                TextEntry::make('items_total')->label('Total')
                    ->formatStateUsing(fn ($v, $r) => number_format($r->items_total, 2, '.', ' ') . ' ' . $r->currency),
                TextEntry::make('paid_total')->label('Payé')
                    ->formatStateUsing(fn ($v, $r) => number_format($r->paid_total, 2, '.', ' ') . ' ' . $r->currency),
                TextEntry::make('balance')->label('Solde')
                    ->formatStateUsing(fn ($v, $r) => number_format($r->balance, 2, '.', ' ') . ' ' . $r->currency),
                TextEntry::make('ordered_at')->label('Date')->dateTime('d/m/Y H:i'),
            ])->columns(3),
        ]);
    }

    // (facultatif) actions d’en-tête
    protected function getHeaderActions(): array
    {
        return [
            // \Filament\Actions\EditAction::make(),
        ];
    }
}
