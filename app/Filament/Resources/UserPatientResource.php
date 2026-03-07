<?php

namespace App\Filament\Resources;

use App\Models\ProxyPatient;
use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserPatientResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Proxydoc';

    protected static ?string $navigationLabel = 'Utilisateurs & Patients';

    protected static ?string $modelLabel = 'Utilisateur';

    protected static ?string $pluralModelLabel = 'Utilisateurs & Patients';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('firstname')->label('Prénom')->searchable()->sortable(),
                TextColumn::make('lastname')->label('Nom')->searchable()->sortable(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('phone')->label('Téléphone')->searchable()->toggleable(),
                TextColumn::make('status')->label('Statut compte')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        0 => 'Supprimé', 1 => 'Activé', 2 => 'En attente', 3 => 'Désactivé',
                        4 => 'Validé', 5 => 'OTP à valider', default => '—',
                    })
                    ->badge()
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success', 2 => 'warning', 3 => 'gray', 5 => 'warning', default => 'gray',
                    }),
                TextColumn::make('has_patient')
                    ->label('Fiche patient')
                    ->getStateUsing(fn ($record) => $record->hasPatientRecord() ? 'Oui' : 'Non')
                    ->badge()
                    ->color(fn ($record) => $record->hasPatientRecord() ? 'success' : 'warning'),
                TextColumn::make('created_at')->label('Inscrit le')->date('d/m/Y')->sortable()->toggleable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('has_patient')
                    ->label('Fiche patient')
                    ->options([
                        'yes' => 'Avec fiche patient',
                        'no'  => 'Sans fiche patient',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['value'] ?? null) === 'yes') {
                            return $query->whereHas('patient', fn ($q) => $q->where('relation', 'self'));
                        }
                        if (($data['value'] ?? null) === 'no') {
                            return $query->whereDoesntHave('patient', fn ($q) => $q->where('relation', 'self'));
                        }
                        return $query;
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view_user')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record) => UserResource::getUrl('view', ['record' => $record])),
                \Filament\Tables\Actions\Action::make('create_patient')
                    ->label('Créer fiche patient')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->visible(fn (User $record) => ! $record->hasPatientRecord())
                    ->action(function (User $record) {
                        ProxyPatient::create([
                            'user_id'    => $record->id,
                            'created_by' => $record->id, // user parent, pas l'admin connecté
                            'updated_by' => Auth::id(),
                            'status'     => 1,
                            'fullname'   => $record->getFullnameAttribute(),
                            'birthdate'  => $record->birth_date ?? now(),
                            'gender'     => $record->gender === 'M' ? 'male' : ($record->gender === 'F' ? 'female' : 'other'),
                            'relation'   => 'self',
                            'phone'      => $record->phone,
                            'email'      => $record->email,
                        ]);
                        \Filament\Notifications\Notification::make()
                            ->title('Fiche patient créée')
                            ->body("La fiche patient pour {$record->getFullnameAttribute()} a été créée.")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Créer la fiche patient')
                    ->modalDescription(fn (User $record) => "Créer une fiche patient pour {$record->getFullnameAttribute()} ? Les informations de base seront préremplies depuis le compte utilisateur."),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserPatientResource\Pages\ListUserPatients::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPolicy(): ?string
    {
        return \App\Policies\UserPatientResourcePolicy::class;
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
        return $user->can('view_any_user::patient');
    }

    public static function canView($record): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        if ($user->hasAnyRole([config('filament-shield.super_admin.name', 'super_admin'), 'Admin'])) {
            return true;
        }
        return $user->can('view_user::patient');
    }
}
