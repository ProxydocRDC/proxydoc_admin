<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use App\Models\ProxyRefAcademicTitle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;

class ProxyRefAcademicTitleResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = ProxyRefAcademicTitle::class;

    protected static ?string $navigationGroup = 'Référentiels';
    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationLabel = 'Titres académiques';
    protected static ?string $modelLabel      = 'Titre académique';
    protected static ?string $pluralModelLabel = 'Titres académiques';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('code')->label('Code')->required()->maxLength(32)->columnSpan(1),
                Forms\Components\TextInput::make('label')->label('Libellé')->required()->maxLength(100)->columnSpan(1),
                Forms\Components\TextInput::make('rate')->label('Taux (%)')->numeric()->minValue(0)->step('0.01')->required()->columnSpan(1),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Code')->searchable(),
            TextColumn::make('label')->label('Libellé')->searchable(),
            TextColumn::make('rate')->label('Taux')->formatStateUsing(fn ($state) => number_format($state, 2).' %')->alignRight(),
            TextColumn::make('status')->label('Statut')->badge()
                ->color(fn ($state) => (int)$state === 1 ? 'success' : 'danger')
                ->formatStateUsing(fn ($state) => (int)$state === 1 ? 'Actif' : 'Inactif'),
        ])
            ->filters([...array_filter([static::getTrashFilter()])])
            ->actions([
            Tables\Actions\EditAction::make(),
            TrashAction::make(),
        ])->bulkActions([
            TrashBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ProxyRefAcademicTitleResource\Pages\ListProxyRefAcademicTitles::route('/'),
            // 'create' => ProxyRefAcademicTitleResource\Pages\CreateProxyRefAcademicTitle::route('/create'),
            // 'edit'   => ProxyRefAcademicTitleResource\Pages\EditProxyRefAcademicTitle::route('/{record}/edit'),
        ];
    }
}
