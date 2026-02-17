<?php
namespace App\Filament\Resources\ChemPharmacyResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';    // <-- nom de la relation sur ChemPharmacy
    protected static ?string $title       = 'Commandes'; // intitulé de l’onglet

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('N°')
                    ->sortable(),

                TextColumn::make('customer.firstname')
                    ->label('Client')
                    ->searchable(),

                BadgeColumn::make('order_status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'created'                     => 'Créée',
                        'pending'                     => 'En attente',
                        'paid'                        => 'Payée',
                        'canceled'                    => 'Annulée',
                        'accepted'                    => 'Acceptée',
                        default                       => ucfirst((string) $state),
                    })
                    ->color(fn($state) => match ($state) {
                        'created'          => 'gray',
                        'pending'          => 'warning',
                        'paid'             => 'success',
                        'canceled'         => 'danger',
                        'accepted'         => 'info',
                        default            => 'secondary',
                    }),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->alignRight()
                    ->sortable()
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, ',', ' ')
                        . ' ' . ($record->currency ?? 'USD')
                    ),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_status')
                    ->label('Statut')
                    ->options([
                        'created'  => 'Créée',
                        'pending'  => 'En attente',
                        'paid'     => 'Payée',
                        'canceled' => 'Annulée',
                        'accepted' => 'Acceptée',
                    ]),
            ])
            ->headerActions([
                // Si tu veux autoriser la création de commandes depuis l’onglet :
                Tables\Actions\CreateAction::make()->visible(fn() => Auth::user()?->hasRole('super_admin')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()->visible(fn() => Auth::user()?->hasRole('super_admin')),
                \App\Filament\Actions\TrashAction::make()->visible(fn() => Auth::user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                \App\Filament\Actions\TrashBulkAction::make()->visible(fn() => Auth::user()?->hasRole('super_admin')),
            ]);
        // Très important : on s’assure que seules les commandes de CETTE pharmacie s’affichent
        // ->modifyQueryUsing(fn (Builder $q) => $q->where('pharmacy_id', $this->getOwnerRecord()->id));
    }
}
