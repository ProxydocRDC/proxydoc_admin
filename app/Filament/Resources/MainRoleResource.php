<?php
namespace App\Filament\Resources;

use App\Filament\Resources\MainRoleResource\Pages;
use App\Models\MainRole;
use Filament\Forms\Components\MultiSelect;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MainRoleResource extends Resource
{
    protected static ?string $model            = MainRole::class;
    protected static ?string $navigationIcon   = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel  = 'Rôles';
    protected static ?string $pluralModelLabel = 'Rôles';
    protected static ?string $slug             = 'roles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('role_name')
                    ->label('Nom du rôle')
                    ->required(),

                MultiSelect::make('permissions')
                    ->label('Permissions associées')
                    ->relationship('permissions', 'permission_name'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('role_name')->label('Nom du rôle'),
                TextColumn::make('permissions.permission_name')
                    ->label('Permissions')
                    ->limit(3)
                    ->badge()
                    ->separator(','),
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
            'index'  => Pages\ListMainRoles::route('/'),
            'create' => Pages\CreateMainRole::route('/create'),
            'edit'   => Pages\EditMainRole::route('/{record}/edit'),
        ];
    }
}
