<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use Filament\Forms;
use Filament\Tables;
use App\Models\Feature;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProxyFeature;
use App\Models\ProxyPlanFeature;
use App\Models\SubscriptionPlan;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Models\ProxySubscriptionPlan;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

class ProxyPlanFeatureResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = ProxyPlanFeature::class;

    protected static ?string $navigationGroup = 'Abonnements';
    protected static ?string $navigationIcon  = 'heroicon-o-list-bullet';
    protected static ?string $navigationLabel = 'Fonctionnalités des plans';
    protected static ?string $modelLabel      = 'Fonctionnalité de plan';
    protected static ?string $pluralModelLabel = 'Fonctionnalités de plan';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Lien plan ↔ fonctionnalité')->schema([
                    Forms\Components\Select::make('plan_id')
                        ->label('Plan')
                        ->options(fn () => SubscriptionPlan::query()
                            ->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->preload()->required()
                        ->columnSpan(6),

                    Forms\Components\Select::make('feature_id')
                        ->label('Fonctionnalité')
                        ->options(fn () => Feature::query()
                            ->orderBy('name')->pluck('name', 'id'))
                        ->searchable()->preload()->required()
                        ->columnSpan(6),

                    Forms\Components\Toggle::make('is_included')
                        ->label('Incluse dans le plan ?')
                        ->default(true)
                        ->inline(false)
                        ->columnSpan(6),

                    Forms\Components\Select::make('status')
                        ->label('Statut')
                        ->options([1 => 'Actif', 0 => 'Inactif'])
                        ->default(1)
                        ->columnSpan(6),

                    Forms\Components\Hidden::make('created_by')->default(Auth::id()),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('plan.name')
                    ->label('Plan')
                    ->formatStateUsing(function ($state, $record) {
                        $label = [
                            'personal'   => 'Personnel',
                            'family'     => 'Famille',
                            'enterprise' => 'Entreprise',
                        ][$record->plan?->type] ?? $record->plan?->type;

                        return $state && $label ? "{$state} ({$label})" : ($state ?? '—');
                    })
                    // recherche sur nom & type du plan
                    ->searchable(
                        query: fn (Builder $query, string $search) =>
                            $query->whereHas('plan', fn (Builder $q) =>
                                $q->where('name', 'like', "%{$search}%")
                                ->orWhere('type', 'like', "%{$search}%")
                            )
                    )
                    ->sortable(),
                TextColumn::make('feature.name')->label('Fonctionnalité')->searchable()->sortable(),
                IconColumn::make('is_included')->label('Incluse ?')->boolean(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => (int)$state === 1 ? 'Actif' : 'Inactif')
                    ->badge()
                    ->color(fn ($state) => (int)$state === 1 ? 'success' : 'danger'),
                TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_id')
                    ->label('Plan')
                    ->options(fn () => SubscriptionPlan::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('feature_id')
                    ->label('Fonctionnalité')
                    ->options(fn () => Feature::orderBy('name')->pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('is_included')
                    ->label('Incluse ?')
                    ->options([1 => 'Oui', 0 => 'Non']),
                ...array_filter([static::getTrashFilter()]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                TrashAction::make()->label('Mettre à la corbeille'),
            ])
            ->bulkActions([
                TrashBulkAction::make()->label('Mettre à la corbeille'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ProxyPlanFeatureResource\Pages\ListProxyPlanFeatures::route('/'),
            'create' => ProxyPlanFeatureResource\Pages\CreateProxyPlanFeature::route('/create'),
            'edit'   => ProxyPlanFeatureResource\Pages\EditProxyPlanFeature::route('/{record}/edit'),
        ];
    }
}
