<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\MainPermission;
use Filament\Resources\Resource;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\MainPermissionResource\Pages;
use App\Filament\Resources\MainPermissionResource\RelationManagers;

class MainPermissionResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
 protected static ?string $model = MainPermission::class;
    protected static ?string $navigationLabel = 'Permissions';
    protected static ?string $pluralModelLabel = 'Permissions';
    protected static ?string $slug = 'permissions';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('permission_name')
                    ->label('Nom de la permission')
                    ->required(),
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
            'index' => Pages\ListMainPermissions::route('/'),
            'create' => Pages\CreateMainPermission::route('/create'),
            'edit' => Pages\EditMainPermission::route('/{record}/edit'),
        ];
    }
}
