<?php

namespace App\Filament\Resources\MainPaymentResource\Pages;

use App\Filament\Resources\MainPaymentResource;
use App\Models\MainPayment;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;

class ViewMainPayment extends ViewRecord
{
    protected static string $resource = MainPaymentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informations transaction')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('id')->label('ID'),
                        TextEntry::make('created_at')
                            ->label('Date')
                            ->dateTime('d/m/Y H:i'),
                        TextEntry::make('method')
                            ->label('Méthode')
                            ->badge()
                            ->formatStateUsing(fn (?string $state) => $state === MainPayment::METHOD_CARD ? 'Carte' : 'Mobile Money')
                            ->color(fn (?string $state) => $state === MainPayment::METHOD_CARD ? 'info' : 'primary'),
                        TextEntry::make('channel')->label('Canal / Opérateur'),
                        TextEntry::make('currency')->label('Devise')->badge(),
                        TextEntry::make('amount')
                            ->label('Montant payé')
                            ->money(fn ($record) => $record->currency ?? 'USD'),
                        TextEntry::make('total_amount')
                            ->label('Montant total')
                            ->money(fn ($record) => $record->currency ?? 'USD'),
                        TextEntry::make('payment_status')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(fn ($record) => $record->payment_status_label)
                            ->color(fn ($record) => $record->payment_status_color),
                        TextEntry::make('telephone')->label('Téléphone payeur'),
                    ]),

                Section::make('Références')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_number')->label('N° transaction'),
                        TextEntry::make('reference')->label('Référence interne'),
                        TextEntry::make('provider_reference')->label('Référence gateway'),
                        TextEntry::make('gateway')->label('Gateway'),
                        TextEntry::make('ip_address')->label('Adresse IP'),
                    ]),

                Section::make('Liens')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('order_id')
                            ->label('Commande')
                            ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '—'),
                        TextEntry::make('subscription_id')
                            ->label('Abonnement')
                            ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '—'),
                        TextEntry::make('appointment_id')
                            ->label('Rendez-vous')
                            ->formatStateUsing(fn ($state) => $state ? "#{$state}" : '—'),
                        TextEntry::make('creator.name')
                            ->label('Créé par')
                            ->formatStateUsing(fn ($state) => $state ?? '—'),
                    ]),
            ]);
    }
}
