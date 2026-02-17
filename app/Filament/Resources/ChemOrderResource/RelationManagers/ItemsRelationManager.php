<?php

namespace App\Filament\Resources\ChemOrderResource\RelationManagers;

use Filament\Forms\Get;

// ⚠️ Ces deux imports manquants causent exactement ton erreur :
use Filament\Forms\Set;
use Filament\Forms\Form;

// Imports des composants de formulaire
use Filament\Tables\Table;
use App\Models\ChemProduct;
use App\Models\ChemPharmacyProduct;

// Imports des colonnes de table
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;

// (facultatif) Notifications, actions, etc. si tu en utilises
// use Filament\Notifications\Notification;
class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';
    protected static ?string $title = 'Lignes';

    public function form(Form $form): Form
    {
        return $form->schema([
            Group::make([
                Section::make('Ligne de commande')->schema([
                    Hidden::make('created_by')->default(Auth::id()),

                    // 1) Produit vendu par la pharmacie de la commande
                     Select::make('pharmacy_product_id')
                ->label('Produit (pharmacie sélectionnée)')
                ->required()
                ->searchable()
                ->preload()
                ->options(function (RelationManager $livewire) {
                    // Récupère la commande parente
                    $order = $livewire->getOwnerRecord();
                    $query = \App\Models\ChemPharmacyProduct::query()
                        ->with('product')
                        ->when($order?->pharmacy_id, fn ($q) => $q->where('pharmacy_id', $order->pharmacy_id))
                        ->orderBy('id', 'desc');

                    return $query->get()->mapWithKeys(function ($pp) {
                        $name  = $pp->product?->name ?? "Produit #{$pp->product_id}";
                        $price = number_format((float) $pp->sale_price, 2, '.', ' ');
                        return [$pp->id => "{$name} — {$price}"];
                    })->toArray();
                })
                ->helperText('Liste des produits proposés par la pharmacie de cette commande.')
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if (! $state) return;

                    $pp = \App\Models\ChemPharmacyProduct::with('product')->find($state);
                    if (! $pp) return;

                    // Alimente automatiquement
                    $set('product_id', $pp->product_id);
                    $set('unit_price', (float) $pp->sale_price);

                    $qty = (float) ($get('quantity') ?? 1);
                    $qty = $qty ?: 1;
                    $set('quantity', $qty);
                    $set('subtotal', round(((float) $pp->sale_price) * $qty, 2));
                }),


                    // product_id stocké mais non éditable (renseigné automatiquement)
                    Select::make('product_id')
                        ->label('Produit (référence)')
                        ->options(fn () => ChemProduct::query()->orderBy('name')->pluck('name', 'id'))
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Déduit automatiquement du produit choisi ci-dessus.'),

                    TextInput::make('quantity')
                        ->label('Quantité')
                        ->numeric()
                        ->minValue(0.01)
                        ->step('0.01')
                        ->default(1)
                        ->reactive()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $unit = (float) ($get('unit_price') ?? 0);
                            $qty  = (float) ($state ?? 0);
                            $set('subtotal', round($unit * $qty, 2));
                        })
                        ->required()
                        ->helperText('Quantité à facturer.'),

                    TextInput::make('unit_price')
                        ->label('Prix unitaire')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->disabled()    // non modifiable
                        ->dehydrated()  // mais bien envoyé au backend
                        ->required()
                        ->helperText('Renseigné automatiquement à partir du produit-pharmacie.'),

                    TextInput::make('subtotal')
                        ->label('Sous-total')
                        ->numeric()
                        ->minValue(0)
                        ->step('0.01')
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->helperText('Calculé automatiquement = quantité × prix unitaire.'),



                ])->columns(2),
            ])->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                // Produit (depuis la relation)
                TextColumn::make('product.name')
                    ->label('Produit')
                    ->searchable(),

                // Affiche la pharmacie du parent pour rappel (non stockée dans l’item)
                TextColumn::make('order.pharmacy.name')
                    ->label('Pharmacie')
                    ->toggleable(),

                TextColumn::make('quantity')
                    ->label('Qté')
                    ->alignRight(),

                TextColumn::make('unit_price')
                    ->label('PU')
                    ->alignRight()
                     ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ')
                    ),

                TextColumn::make('subtotal')
                    ->label('Sous-total')
                    ->alignRight()
                    ->formatStateUsing(fn($state, $record) =>
                        number_format((float) $state, 2, '.', ' ')
                    )
                    ->sortable(),
            ])
        ->headerActions([
            \Filament\Tables\Actions\CreateAction::make()->label('Ajouter une ligne'),
        ])
        ->actions([
            \Filament\Tables\Actions\EditAction::make(),
            \App\Filament\Actions\TrashAction::make(),
        ])
        ->paginated(false);
    }
    /** pour compléter created_by / updated_by depuis le relation manager */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
