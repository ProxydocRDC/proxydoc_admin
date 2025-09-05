<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ProxyPatientResource\Pages;
use App\Models\ProxyPatient;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class ProxyPatientResource extends Resource
{
    protected static ?string $model = ProxyPatient::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Proxydoc';
    protected static ?string $navigationLabel = 'Patients';
    protected static ?string $modelLabel      = 'Patient';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make('Fiche patient')->schema([
                    Hidden::make('created_by')->default(fn() => Auth::id()),
                    Hidden::make('updated_by')->default(fn() => Auth::id())->dehydrated(),
                    Select::make('user_id')
                        ->label('Utilisateur parent')
                        ->relationship('user', 'firstname') // <-- pas besoin de closure
                        ->searchable()
                        ->preload()->columnSpan(4)
                        ->required(),

                    TextInput::make('fullname')->label('Nom complet')
                        ->required()->maxLength(200)->columnSpan(4),

                    DatePicker::make('birthdate')->label('Naissance')
                        ->required()->native(false)->columnSpan(4),

                    Select::make('gender')->label('Genre')->required()
                        ->options(['male' => 'Homme', 'female' => 'Femme', 'other' => 'Autre'])
                        ->columnSpan(4),

                    Select::make('blood_group')->label('Groupe sanguin')
                        ->options([
                            'A+'  => 'A+', 'A-'   => 'A-', 'B+'  => 'B+', 'B-' => 'B-',
                            'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-',
                        ])->columnSpan(4),

                    // Select::make('relation')->label('Relation')
                    //     ->options([
                    //         'self'=>'Moi-même','child'=>'Enfant','parent'=>'Parent',
                    //         'spouse'=>'Conjoint','sibling'=>'Frère/soeur','friend'=>'Ami','other'=>'Autre',
                    //     ])->default('self')->required()->columnSpan(4),
                    Select::make('relation')->label('Relation')
                        ->options([
                            'self'   => 'Moi-même', 'child'   => 'Enfant', 'parent'      => 'Parent',
                            'spouse' => 'Conjoint', 'sibling' => 'Frère/soeur', 'friend' => 'Ami', 'other' => 'Autre',
                        ])
                        ->default('self')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function (string $state, Set $set) {
                            if ($state === 'self') {
                                $set('user_id', Auth::id()); // rattacher au compte connecté
                            } else {
                                // on ne force rien, l’admin choisit l’utilisateur parent
                                // $set('user_id', null); // <- décommente si tu veux effacer à chaque changement
                            }
                        })
                        ->columnSpan(4),

                    TextInput::make('phone')->label('Téléphone')->tel()->maxLength(20)->columnSpan(6),
                    TextInput::make('email')->label('Email')->email()->maxLength(150)->columnSpan(6),

                    // Les colonnes sont TEXT : on stocke du JSON (cast array côté modèle)
                    KeyValue::make('allergies')->label('Allergies')->columnSpan(6)->reorderable(),
                    KeyValue::make('chronic_conditions')->label('Maladies chroniques')->columnSpan(6)->reorderable(),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('user.fullname')
                    ->label('Utilisateur parent')
                    ->description(fn($record) => $record->user?->firstname) // petit complément sous le nom
                    ->toggleable(),
                TextColumn::make('fullname')->label('Nom')->searchable()->sortable(),
                TextColumn::make('birthdate')->date('Y-m-d')->label('Naissance')->sortable(),
                BadgeColumn::make('gender')->label('Genre')
                    ->colors([
                        'info'    => 'male',
                        'warning' => 'female',
                        'gray'    => 'other',
                    ])
                    ->formatStateUsing(fn(string $state) => [ // <-- $state (pas $s)
                        'male'   => 'Homme',
                        'female' => 'Femme',
                        'other'  => 'Autre',
                    ][$state] ?? $state),
                TextColumn::make('blood_group')->label('Groupe'),
                TextColumn::make('relation')->label('Relation')
                    ->formatStateUsing(fn(string $state) => [ // <-- $state (pas $s)
                        'self'    => 'Moi-même',
                        'child'   => 'Enfant',
                        'parent'  => 'Parent',
                        'spouse'  => 'Conjoint',
                        'sibling' => 'Frère/soeur',
                        'friend'  => 'Ami',
                        'other'   => 'Autre',
                    ][$state] ?? $state),
                TextColumn::make('phone')->label('Téléphone')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')->label('Email')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Actif'),
                Tables\Filters\SelectFilter::make('gender')->label('Genre')
                    ->options(['male' => 'Homme', 'female' => 'Femme', 'other' => 'Autre']),
                Tables\Filters\SelectFilter::make('blood_group')->label('Groupe')
                    ->options(['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-', 'O+' => 'O+', 'O-' => 'O-']),
                Tables\Filters\SelectFilter::make('relation')->label('Relation')
                    ->options(['self' => 'Moi-même', 'child' => 'Enfant', 'parent' => 'Parent', 'spouse' => 'Conjoint', 'sibling' => 'Frère/soeur', 'friend' => 'Ami', 'other' => 'Autre']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyPatients::route('/'),
            'create' => Pages\CreateProxyPatient::route('/create'),
            'view'   => Pages\ViewProxyPatient::route('/{record}'),
            'edit'   => Pages\EditProxyPatient::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['relation'] ?? null) === 'self') {
            $data['user_id'] = $data['user_id'] ?? Auth::id();
        }
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();
        $data['status']     = $data['status'] ?? 1;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }

}
