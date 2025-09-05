<?php
namespace App\Filament\Resources;

use App\Filament\Resources\ChemPharmacyProductResource\Pages;
use App\Models\ChemPharmacyProduct;
use App\Support\Filament\RestrictToSupplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ChemPharmacyProductResource extends Resource
{

    use RestrictToSupplier;

    protected static ?string $model = ChemPharmacyProduct::class;

    protected static ?string $navigationIcon   = 'heroicon-o-shopping-bag'; // Icône adaptée pour un produit
    protected static ?string $navigationLabel  = 'Produits Pharmacies';
    protected static ?string $modelLabel       = 'Produit de Pharmacie';
    protected static ?string $pluralModelLabel = 'Produits de Pharmacies';
    protected static ?string $navigationGroup  = 'Gestion Pharmacie';
    protected static ?int $navigationSort      = 6;

    protected static function supplierOwnerPath(): string
    {
        return 'pharmacy.supplier_id'; // via relation
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire Produit Pharmacie")->schema([
                        Hidden::make('created_by')->default(Auth::id()),


                        Select::make('pharmacy_id')
                            ->label('Pharmacie')
                            ->options(function () {
                                $u    = Auth::user();
                                $base = \App\Models\ChemPharmacy::query();

                                if ($u?->hasRole('super_admin')) {
                                    return $base->orderBy('name')->pluck('name', 'id');
                                }

                                $sid = $u?->supplier?->id ?? 0;
                                return $base->where('supplier_id', $sid)->orderBy('name')->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        // ❌ SUPPRIMER ce bloc : relation inexistante et conceptuellement faux ici
                        // Select::make('pharmacies')->multiple()->relationship('pharmacies', ...)
                        Select::make('product_id')
                            ->label('Produit')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        // Select::make('product_id')
                        //     ->label('Produit')
                        //     ->relationship('product', 'name')
                        //     ->searchable()
                        //     ->preload()
                        //     ->required()
                        //     ->helperText('Choisissez le produit pharmaceutique dans la liste.'),

                        TextInput::make('lot_ref')->label('Référence du lot')->maxLength(50),
                        TextInput::make('origin_country')->label('Code pays d\'origine')->maxLength(3)->default('COD'),

                        DatePicker::make('expiry_date')->label('Date d\'expiration'),

                        TextInput::make('cost_price')->label('Prix d\'achat')->numeric()->prefix('$'),
                        TextInput::make('sale_price')->label('Prix de vente')->numeric()->prefix('$')->required(),

                        Select::make('currency')->label('Devise')->options([
                            'USD' => 'USD - Dollar américain',
                            'CDF' => 'CDF - Franc congolais',
                        ])->required(),

                        TextInput::make('stock_qty')->label('Quantité en stock')->numeric()->default(0)->required(),
                        TextInput::make('reorder_level')->label('Niveau d\'alerte stock')->numeric(),

                        FileUpload::make('image')
                            ->label('Image du produit')
                            ->image()
                            ->directory('pharmacy_products')
                            ->imageEditor()
                            ->disk('s3') // Filament uploade direct vers S3
                            ->visibility('private') // retire si privé
                            ->enableDownload() // (au lieu de ->downloadable())
                            ->enableOpen()
                            ->helperText('Téléchargez une image du produit (max 2 Mo).'),

                        Textarea::make('description')->label('Description')->maxLength(1000),
                    ])->columns(2),

                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            ImageColumn::make('image')
                ->label('Image')
                ->square()
                ->size(50)
                ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('images/PROFI-TIK.jpg'))  // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('image', ttl: 5)), // clic = grande image,

            TextColumn::make('pharmacy.name')
                ->label('Pharmacie')
                ->sortable()
                ->searchable(),

            TextColumn::make('product.name')
                ->label('Produit')
                ->sortable()
                ->searchable(),

            TextColumn::make('lot_ref')
                ->label('Lot Ref'),

            TextColumn::make('origin_country')
                ->label('Pays Origine'),

            TextColumn::make('expiry_date')
                ->label('Expiration')
                ->date('d/m/Y'),

            TextColumn::make('sale_price')
                ->label('Prix Vente')
                ->money(fn($record) => $record->currency),

            TextColumn::make('stock_qty')
                ->label('Stock'),

            TextColumn::make('created_by')
                ->label('Créé par')
                ->formatStateUsing(fn($state) => \App\Models\User::find($state)?->name ?? '—'),
        ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\DeleteAction::make()->label('Supprimer'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Supprimer sélection'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemPharmacyProducts::route('/'),
            'create' => Pages\CreateChemPharmacyProduct::route('/create'),
            'edit'   => Pages\EditChemPharmacyProduct::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'fournisseur']) ?? false;
    }
}
