<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasTrashableRecords;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use App\Models\proxy_categories;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

class ProxyCategoryResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = proxy_categories::class;

    protected static ?string $navigationIcon   = 'heroicon-o-tag';
    protected static ?string $navigationGroup  = 'Paramètres';
    protected static ?int    $navigationSort   = 10;
    protected static ?string $modelLabel       = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';

    // Visible seulement pour l’admin (ou adapte à ton Shield)
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin','admin']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
              Group::make([
                Section::make('Créneau une catégorie')->schema([
            Hidden::make('created_by')->default(fn () => Auth::id()),
            Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(false),


            TextInput::make('name')
                ->label('Nom')
                ->required()
                ->columnSpan(6)
                ->maxLength(100)
                ->unique(ignoreRecord: true),

            TextInput::make('commission')
                ->label('Commission (%)')
                ->numeric()
                ->columnSpan(6)
                ->minValue(0)->maxValue(100)->step('0.01')
                ->helperText('Pourcentage appliqué (0–100).'),

            Textarea::make('description')
                ->label('Description')
                ->columnSpan(12)
                ->maxLength(100),
                 ])->columns(12),
            ])->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable()->toggleable(),

                TextColumn::make('name')->label('Nom')->sortable()->searchable(),

                TextColumn::make('commission')
                    ->label('Commission %'),

                TextColumn::make('description')->limit(40),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options([1 => 'Actif', 0 => 'Inactif']),
                ...array_filter([static::getTrashFilter()]),
            ])
            ->actions([
                Actions\EditAction::make(),
                \App\Filament\Actions\TrashAction::make(),
                \App\Filament\Actions\RestoreFromTrashAction::make(),
                Actions\ForceDeleteAction::make()
                    ->visible(fn () => Auth::user()?->hasRole('super_admin')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    \App\Filament\Actions\TrashBulkAction::make(),
                    \App\Filament\Actions\RestoreFromTrashAction::makeBulk(),
                    Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ProxyCategoryResource\Pages\ListProxyCategories::route('/'),
            // 'create' => ProxyCategoryResource\Pages\CreateProxyCategory::route('/create'),
            // 'edit'   => ProxyCategoryResource\Pages\EditProxyCategory::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name','description'];
    }
}
