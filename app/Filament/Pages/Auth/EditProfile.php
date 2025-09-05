<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\EditProfile as BaseEditProfile;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('firstname')->label('Prénom')->required()->maxLength(255),
            TextInput::make('lastname')->label('Nom')->maxLength(255),
            TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
            TextInput::make('password')
                ->label('Nouveau mot de passe')
                ->password()
                ->revealable()
                ->dehydrated(fn ($state) => filled($state))
                ->maxLength(255),
        ]);
    }

    // On contrôle exactement quoi est écrit en base (pas de 'name')
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->firstname = $data['firstname'];
        $record->lastname  = $data['lastname'] ?? null;
        $record->email     = $data['email'];

        if (! empty($data['password'])) {
            $record->password = Hash::make($data['password']);
        }

        $record->save();

        return $record;
    }
}
