<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Actions\TrashAction;
use App\Models\ProxyPatient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PatientsRelationManager extends RelationManager
{
    protected static string $relationship = 'patient';

    protected static ?string $title = 'Patients liés';

    protected static ?string $recordTitleAttribute = 'fullname';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('relation')
                ->label('Relation')
                ->options([
                    'self'   => 'Moi-même', 'child'   => 'Enfant', 'parent' => 'Parent',
                    'spouse' => 'Conjoint', 'sibling' => 'Frère/soeur', 'friend' => 'Ami', 'other' => 'Autre',
                ])
                ->default('other')
                ->required()
                ->columnSpan(4),
            TextInput::make('fullname')->label('Nom complet')->required()->maxLength(200)->columnSpan(4),
            DatePicker::make('birthdate')->label('Date de naissance')->required()->native(false)->columnSpan(4),
            Select::make('gender')->label('Genre')->required()
                ->options(['male' => 'Homme', 'female' => 'Femme', 'other' => 'Autre'])
                ->columnSpan(4),
            Select::make('blood_group')->label('Groupe sanguin')
                ->options([
                    'A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-',
                    'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-',
                ])
                ->columnSpan(4),
            TextInput::make('phone')->label('Téléphone')->tel()->maxLength(20)->columnSpan(4),
            TextInput::make('email')->label('Email')->email()->maxLength(150)->columnSpan(4),
            KeyValue::make('allergies')->label('Allergies')->columnSpan(6)->reorderable(),
            KeyValue::make('chronic_conditions')->label('Maladies chroniques')->columnSpan(6)->reorderable(),
        ])->columns(12);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fullname')->label('Nom')->searchable()->sortable(),
                TextColumn::make('birthdate')->date('d/m/Y')->label('Naissance')->sortable(),
                TextColumn::make('gender')->label('Genre')->formatStateUsing(fn ($state) => match ($state) {
                    'male' => 'Homme', 'female' => 'Femme', default => $state ?? '—',
                }),
                TextColumn::make('blood_group')->label('Groupe sanguin'),
                TextColumn::make('relation')->label('Relation')->formatStateUsing(fn ($state) => match ($state) {
                    'self' => 'Moi-même', 'child' => 'Enfant', 'parent' => 'Parent',
                    'spouse' => 'Conjoint', 'sibling' => 'Frère/soeur', 'friend' => 'Ami', default => $state ?? '—',
                }),
                TextColumn::make('phone')->label('Téléphone')->toggleable(),
                TextColumn::make('email')->label('Email')->toggleable(),
            ])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $this->getOwnerRecord()->id;
                        $data['created_by'] = Auth::id();
                        $data['updated_by'] = Auth::id();
                        $data['status'] = 1;
                        return $data;
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                TrashAction::make(),
            ]);
    }
}
