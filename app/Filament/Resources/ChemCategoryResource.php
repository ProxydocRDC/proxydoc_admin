<?php
namespace App\Filament\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use App\Models\ChemCategory;
use Filament\Resources\Resource;
use Illuminate\Support\Collection;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ChemCategoryResource\Pages;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ChemCategoryResource extends Resource
{
    protected static ?string $model = ChemCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationGroup  = 'Référentiels';
    protected static ?string $navigationLabel  = 'Catégories';
    protected static ?string $modelLabel       = 'Catégorie';
    protected static ?string $pluralModelLabel = 'Catégories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make("Formulaire pour ajouter une catégorie")->schema([
                        Hidden::make('created_by')
                            ->default(Auth::id()),
                        TextInput::make('name')
                            ->label('Nom de la catégorie')
                            ->required()
                            ->columnSpan(6)
                            ->reactive()
                            ->live(onBlur: true) // <-- déclenche la mise à jour seulement quand on quitte le champ
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if (blank($state) || filled($get('code'))) {
                                    return;
                                }
                                $set('code', self::generateHospitalCode($state));
                            })
                            ->maxLength(100),

                        TextInput::make('code')
                            ->label('Code')
                            ->columnSpan(6)
                            ->maxLength(100)
                            ->unique(
                                table: fn() => app(\App\Models\ChemCategory::class)->getTable(),
                                ignoreRecord: true
                            ),

                        Textarea::make('description')
                            ->label('Description')
                            ->columnSpan(12)
                            ->maxLength(100),

                        FileUpload::make('image')
                            ->label('Image illustrative')
                            ->image()
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->columnSpan(12)
                                     // ->directory('chem_categories')
                            ->disk('s3') // Filament uploade direct vers S3
                            ->directory('categories')
                            ->visibility('private')
                            ->maxSize(10240) // 10 Mo
                            ->openable()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get) {
                                return 'ChemCategorie-' . ($get('name') ? str()->slug($get('name')) . '-' : '') . str()->uuid() . '.' . $file->getClientOriginalExtension();
                            }),
                        Toggle::make('status')
                            ->label('Active (pour cacher ou rendre visible la catégorie)')
                            ->columnSpan(6)
                            ->onColor('success')
                            ->offColor('danger')
                            ->required(),
                    ])->columnS(12),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        // On enrichit la query avec le compteur:
        ->modifyQueryUsing(fn (Builder $q) => $q->withCount('products'))
            ->columns([
                ImageColumn::make('image') // colonne réelle = 'image'
                    ->label('Image')
                    ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg'))  // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('image', ttl: 5)), // clic = grande image

                TextColumn::make('name')
                    ->label('Nom')
                    ->sortable()
                    ->searchable(),
TextColumn::make('products_count')
                ->label('Produits')
                ->badge()              // joli badge
                ->alignCenter()
                ->sortable()
                ->tooltip(fn ($record) => "{$record->products_count} produit(s)"),
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(40),

                BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn($state) => match ($state) {
                        1                             => 'Actif',
                        0                             => 'Inactif',
                        default                       => 'Inconnu',
                    })
                    ->colors([
                        'success' => fn($state) => $state == 1,
                        'danger'  => fn($state)  => $state == 0,
                    ]),

                TextColumn::make('creator.name') // si relation définie
                    ->label('Créé par')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                // TernaryFilter::make('status')
                //     ->label('Statut')
                //     ->boolean()
                //     ->trueLabel('Actifs')
                //     ->falseLabel('Archivés')
                //     ->queries([
                //         'true'  => fn(Builder $q)  => $q->where('status', 1),
                //         'false' => fn(Builder $q) => $q->where('status', 0),
                //     ])
                //     ->default(true),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                // ↘️ Remplace le "Delete" standard : ici on ARCHIVE (status=0)
                Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-m-trash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer (archiver) ?')
                    ->modalDescription('Cette action mettra l’élément en statut inactif.')
                    ->action(fn($record) => $record->update(['status' => 0]))
                    ->after(fn($record) => \Filament\Notifications\Notification::make()
                            ->title('Élément archivé')
                            ->success()
                            ->body("La catégorie {$record->name} est mis en archive.")
                            ->sendToDatabase(Auth::user())
                            ->send())
                    ->visible(fn($record) => (int) $record->status === 1),
                Action::make('unarchive')
                    ->label('Désarchiver')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Désarchiver cet élément ?')
                    ->modalDescription('Le statut repassera à actif.')
                    ->visible(fn($record) =>
                        (int) ($record->status ?? 1) === 0
                        && Auth::user()?->can('update', $record)
                    )
                    ->action(function ($record) {
                        $record->update(['status' => 1]);

                        $label = $record->name ?? $record->title ?? ('#' . $record->getKey());

                        // Toast pour l’auteur
                        Notification::make()
                            ->title('Élément désarchivé')
                            ->body("{$label} a été réactivé.")
                            ->success()
                            ->send();

                        // Notif BD pour l’auteur
                        if ($actor = Auth::user()) {
                            Notification::make()
                                ->title('Élément désarchivé')
                                ->body("{$label} a été réactivé.")
                                ->success()
                                ->sendToDatabase($actor);
                        }

                        // Notif BD pour tous les Super Admins
                        $superRole = config('filament-shield.super_admin.name', 'Super Admin');
                        User::role($superRole)->each(function ($admin) use ($label, $actor) {
                            Notification::make()
                                ->title('Élément désarchivé')
                                ->body("{$label} a été réactivé par " . ($actor->firstname ?? $actor->name ?? 'un utilisateur') . ".")
                                ->success()
                                ->sendToDatabase($admin);
                        });
                    }),
                // ↘️ Suppression DÉFINITIVE
                Action::make('forceDelete')
                    ->label('Supprimer définitivement')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn($record) => (int) $record->status === 0)
                    ->modalHeading('Supprimer définitivement ?')
                    ->modalDescription('Opération irréversible : l’enregistrement sera supprimé de la base.')
                    ->action(function ($record) {
                        // si ton modèle utilise SoftDeletes, appelle forceDelete()
                        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
                            $record->forceDelete();
                        } else {
                            // sinon delete() = suppression définitive
                            $record->delete();
                        }
                        // Notification toast pour l’auteur
                        Notification::make()
                            ->title('Supprimé définitivement')
                            ->body("{$record->name} a été supprimé définitivement.")
                            ->success()
                            ->send();

                        // Notification BD pour l’auteur
                        if ($user = Auth::user()) {
                            Notification::make()
                                ->title('Supprimé définitivement')
                                ->body("La catégorie {$record->name} a été supprimé définitivement.")
                                ->success()
                                ->sendToDatabase($user);
                        }

                        // Notification BD pour tous les Super Admins (rôle défini dans la config Shield)
                        $superRole = config('filament-shield.super_admin.name', 'Super Admin');
                        User::role($superRole)->each(function ($admin) use ($record) {
                            Notification::make()
                                ->title('Suppression définitive')
                                ->body("La catégorie {$record->name} a été supprimé définitivement.")
                                ->success()
                                ->sendToDatabase($admin);
                        });
                    }),

            ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                // archiver en masse
                Tables\Actions\BulkAction::make('archiver')
                    ->label('Supprimer (archiver)')
                    ->icon('heroicon-m-archive-box-x-mark')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn(\Illuminate\Support\Collection $records) =>
                        $records->each->update(['status' => 0])
                    ),
                Tables\Actions\BulkAction::make('unarchive')
                    ->label('Désarchiver')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn(Collection $records) => $records->each->update(['status' => 1])),
                // supprimer définitivement en masse
                Tables\Actions\BulkAction::make('forceDelete')
                    ->label('Supprimer définitivement')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Support\Collection $records) {
                        foreach ($records as $record) {
                            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
                                $record->forceDelete();
                            } else {
                                $record->delete();
                            }
                        }
                    }),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    protected static function generateHospitalCode(?string $name): string
    {
        $initials = collect(preg_split('/[\s\-]+/u', Str::of((string) $name)->squish()))
            ->filter()
            ->reject(fn($w) => in_array(mb_strtolower($w), ['de', 'du', 'des', 'la', 'le', 'les', 'l\'', 'd\'', 'et', 'en', 'à', 'au', 'aux', 'pour', 'sur', 'sous', 'chez'], true))
            ->map(fn($w) => Str::upper(Str::substr(Str::of($w)->replace(['l\'', 'd\''], ''), 0, 1)))
            ->implode('') ?: Str::upper(Str::substr(Str::slug((string) $name), 0, 3));

        return "PROXY-{$initials}-" . random_int(10000, 99999);
    }
    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChemCategories::route('/'),
            'create' => Pages\CreateChemCategory::route('/create'),
            'edit'   => Pages\EditChemCategory::route('/{record}/edit'),
        ];
    }
     public static function getNavigationBadge(): ?string
    {

        $base = ChemCategory::query();


        return (string) $base->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning'; // ou 'success', 'warning', etc.
    }
}
