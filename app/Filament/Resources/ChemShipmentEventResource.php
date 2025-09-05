<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChemShipmentEventResource\Pages;
use App\Filament\Resources\ChemShipmentEventResource\RelationManagers;
use App\Models\ChemShipmentEvent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ChemShipmentEventResource extends Resource
{
    protected static ?string $model = ChemShipmentEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChemShipmentEvents::route('/'),
            'create' => Pages\CreateChemShipmentEvent::route('/create'),
            'edit' => Pages\EditChemShipmentEvent::route('/{record}/edit'),
        ];
    }
     public static function shouldRegisterNavigation(): bool
    {
        return false; // <- n’apparaît plus dans le menu
    }
}
