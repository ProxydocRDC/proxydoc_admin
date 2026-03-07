<?php

namespace App\Filament\Resources;

use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;

class ParrainesResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Paramètres';

    protected static ?string $navigationLabel = 'Parrainés';

    protected static ?string $modelLabel = 'Utilisateur parrainé';

    protected static ?string $pluralModelLabel = 'Utilisateurs parrainés';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNotNull('code_parrainage')
            ->where('code_parrainage', '!=', '');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile')
                    ->label('Profil')
                    ->getStateUsing(fn ($record) => $record->profile_url)
                    ->defaultImageUrl(asset('assets/images/default.jpg'))
                    ->circular()
                    ->height(44),
                TextColumn::make('firstname')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('lastname')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('Téléphone')->searchable()->toggleable(),
                TextColumn::make('code_parrainage')
                    ->label('Code utilisé')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Code copié')
                    ->copyMessageDuration(1500),
                TextColumn::make('parrain_nom')
                    ->label('Parrain')
                    ->getStateUsing(function (User $record) {
                        $parrain = User::where('code_promo', $record->code_parrainage)->first();
                        return $parrain ? $parrain->getFullnameAttribute() : '—';
                    })
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')->label('Statut')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        0 => 'Supprimé', 1 => 'Activé', 2 => 'En attente', 3 => 'Désactivé',
                        4 => 'Validé', 5 => 'OTP à valider', default => '—',
                    })
                    ->badge()
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success', 2 => 'warning', 3 => 'gray', 5 => 'warning', default => 'gray',
                    }),
                TextColumn::make('created_at')->label('Inscrit le')->date('d/m/Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('parrain_id')
                    ->form([
                        Select::make('parrain_id')
                            ->label('Parrain')
                            ->options(
                                User::query()
                                    ->whereNotNull('code_promo')
                                    ->where('code_promo', '!=', '')
                                    ->orderBy('firstname')
                                    ->get()
                                    ->mapWithKeys(fn (User $u) => [$u->id => $u->getFullnameAttribute() . ' (' . $u->code_promo . ')'])
                            )
                            ->searchable()
                            ->placeholder('— Tous —'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['parrain_id'])) {
                            return $query;
                        }
                        $parrain = User::find($data['parrain_id']);
                        return $parrain ? $query->parrainesDe($parrain) : $query;
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => UserResource::getUrl('view', ['record' => $record])),
                \Filament\Tables\Actions\Action::make('edit')
                    ->label('Modifier')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn (User $record) => UserResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\ParrainesResource\Pages\ListParraines::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([config('filament-shield.super_admin.name', 'super_admin'), 'Admin'])) {
            return true;
        }
        return $user->can('view_any_user');
    }
}
