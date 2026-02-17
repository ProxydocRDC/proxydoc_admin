<?php

namespace App\Filament\Resources\ChemShipmentResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\RelationManagers\RelationManager;

class EventsRelationManager extends RelationManager
{
    protected static string $relationship = 'events';
    protected static ?string $title = 'Événements';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('event_type')
                ->label('Type')
                ->options([
                    'created' => 'Créée',
                    'picked_up' => 'Pris en charge',
                    'in_transit' => 'En transit',
                    'arrived_hub' => 'Arrivé hub',
                    'out_for_delivery' => 'En tournée',
                    'delivered' => 'Livrée',
                    'delivery_failed' => 'Échec livraison',
                    'returned_to_sender' => 'Retour à l’expéditeur',
                    'returned' => 'Retournée',
                    'canceled' => 'Annulée',
                    'exception' => 'Exception',
                ])
                ->required(),

            Forms\Components\DateTimePicker::make('event_time')
                ->label('Date/heure')
                ->seconds(false)
                ->default(now())
                ->required(),

            // 2 champs pour remplir le JSON location proprement
            Forms\Components\TextInput::make('location.lat')
                ->label('Latitude')->numeric()->required(),
            Forms\Components\TextInput::make('location.lng')
                ->label('Longitude')->numeric()->required(),

            Forms\Components\Textarea::make('remarks')
                ->label('Remarques')->maxLength(1000)->columnSpanFull(),

            Forms\Components\Hidden::make('created_by')->default(Auth::id()),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event_time')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                Tables\Columns\BadgeColumn::make('event_type')->label('Événement')
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', $state))
                    ->color(fn ($state) => match ($state) {
                        'created' => 'gray',
                        'picked_up', 'in_transit', 'arrived_hub', 'out_for_delivery' => 'info',
                        'delivered' => 'success',
                        'delivery_failed', 'exception' => 'danger',
                        'returned_to_sender', 'returned', 'canceled' => 'warning',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('location.lat')->label('Lat'),
                Tables\Columns\TextColumn::make('location.lng')->label('Lng'),
                Tables\Columns\TextColumn::make('remarks')->label('Remarques')->limit(30),
            ])
            ->defaultSort('event_time', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Ajouter un événement'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                \App\Filament\Actions\TrashAction::make(),
            ])
            ->paginated(false);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] =Auth::id();
        return $data;
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] =Auth::id();
        return $data;
    }
}
