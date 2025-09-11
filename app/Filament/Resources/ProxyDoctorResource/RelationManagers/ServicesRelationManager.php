<?php

// app/Filament/Resources/ProxyDoctorResource/RelationManagers/ServicesRelationManager.php
namespace App\Filament\Resources\ProxyDoctorResource\RelationManagers;


use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProxyDoctor;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\{TextColumn, IconColumn};
use Filament\Resources\RelationManagers\RelationManager;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';
    protected static ?string $title = 'Services';
    protected static ?string $recordTitleAttribute = 'label';

    public function form(Form $form): Form
    {
        // Champs du PIVOT (proxy_plan_features) accessibles via leur nom direct
        return $form->schema([
            Forms\Components\Select::make('status')
                ->label('Statut')
                ->options([1 => 'Actif', 0 => 'Inactif'])
                ->default(1)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Code')->toggleable(),
                TextColumn::make('label')->label('Service')->searchable(),
                IconColumn::make('pivot.status')->label('Actif')->boolean(),
            ])
            ->headerActions([
    Tables\Actions\AttachAction::make()
        ->multiple() // si tu veux en attacher plusieurs d’un coup
        ->preloadRecordSelect()
        ->recordTitleAttribute('label') // colonne d’affichage du service
        ->recordSelectSearchColumns(['label', 'code'])
        ->recordSelectOptionsQuery(
            fn (Builder $q) => $q->where('status', 1)->orderBy('label')
        )
        // champs PIVOT seulement
        ->form([
            Select::make('status')
                ->label('Statut')
                ->options([1 => 'Actif', 0 => 'Inactif'])
                ->default(1)
                ->required(),
        ])
        // Remplir le pivot (version multiple)
        ->using(function (RelationManager $livewire, array $records, array $data): void {
            $pivot = collect($records)->mapWithKeys(fn ($id) => [
                $id => [
                    'status'     => (int)($data['status'] ?? 1),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ],
            ])->all();

            $livewire->getRelationship()->attach($pivot);
        }),
        // Select::make('services')
        //         ->label('Services proposés')
        //         ->relationship('services', 'label')
        //         ->multiple()
        //         ->preload()
        //         ->searchable()
        //         ->saveRelationshipsUsing(function (ProxyDoctor $doctor, ?array $state) {
        //             $pivot = collect($state ?? [])->mapWithKeys(fn ($serviceId) => [
        //                 $serviceId => [
        //                     'status'     => 1,
        //                     'created_by' => Auth::id(),
        //                     'updated_by' => Auth::id(),
        //                 ],
        //             ])->all();

        //         $doctor->services()->sync($pivot);
        //     }),

])
            ->actions([
                Tables\Actions\EditAction::make(),     // édite les champs pivot (status)
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ]);
    }
}
