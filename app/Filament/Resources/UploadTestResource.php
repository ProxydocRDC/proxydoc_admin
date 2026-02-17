<?php
namespace App\Filament\Resources;

use App\Filament\Resources\UploadTestResource\Pages;
use App\Models\UploadTest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UploadTestResource extends Resource
{
    protected static ?string $model = UploadTest::class;

    protected static ?string $navigationIcon   = 'heroicon-o-cloud-arrow-up';
    protected static ?string $navigationGroup  = 'Sandbox / Tests';
    protected static ?int $navigationSort      = 1;
    protected static ?string $modelLabel       = 'Test upload';
    protected static ?string $pluralModelLabel = 'Tests upload';

    public static function shouldRegisterNavigation(): bool
    {
        // Limite l’accès à l’admin (adapte à ton Shield si besoin)
        return Auth::user()?->hasAnyRole(['super_admin', 'admin', 'dev']) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Options de stockage')->columns(3)->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Titre (optionnel)')
                    ->maxLength(150),

                Forms\Components\Select::make('disk')
                    ->label('Disque')
                    ->options([
                        's3'     => 'S3 (AWS)',
                        'public' => 'Public (local)',
                    ])
                    ->default('s3')
                    ->required(),

                Forms\Components\Toggle::make('is_public')
                    ->label('Visibilité publique ?')
                    ->helperText("Si décoché, le lien sera généré avec URL temporaire.")
                    ->default(true),
            ]),

            Forms\Components\Section::make('Fichiers à tester')->columns(2)->schema([
                // --- 1 fichier ---
                Forms\Components\FileUpload::make('single_file')
                    ->label('Fichier unique')
                    ->disk(fn(Forms\Get $get) => $get('disk') ?: 's3')
                    ->directory('testing/single')
                    ->visibility(fn(Forms\Get $get) => $get('is_public') ? 'public' : 'private')
                    ->acceptedFileTypes([
                        'image/*',
                        'application/pdf',
                        'text/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240) // 10 Mo
                    ->openable()
                    ->downloadable()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Forms\Get $get) {
                        return 'ONE-' . ($get('title') ? str()->slug($get('title')) . '-' : '') . str()->uuid() . '.' . $file->getClientOriginalExtension();
                    }),

                // --- Plusieurs fichiers ---
                Forms\Components\FileUpload::make('multiple_files')
                    ->label('Plusieurs fichiers')
                    ->multiple()
                    ->reorderable()
                    ->disk(fn(Forms\Get $get) => $get('disk') ?: 's3')
                    ->directory('testing/multiple')
                    ->visibility(fn(Forms\Get $get) => $get('is_public') ? 'public' : 'private')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->maxSize(4096) // 4 Mo par fichier
                    ->openable()
                    ->downloadable()
                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Forms\Get $get) {
                        return 'MULTI-' . ($get('title') ? str()->slug($get('title')) . '-' : '') . str()->uuid() . '.' . $file->getClientOriginalExtension();
                    }),
            ]),

            Forms\Components\Textarea::make('notes')
                ->label('Notes')
                ->rows(3)
                ->columnSpanFull(),

            Forms\Components\Textarea::make('error_message')
                ->label('Dernière erreur')
                ->disabled()
                ->dehydrated(false) // on ne remplace pas la valeur en DB depuis le form
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Titre')->searchable(),
                Tables\Columns\TextColumn::make('disk')->label('Disque')->badge(),
                Tables\Columns\IconColumn::make('is_public')->label('Public')->boolean(),

                Tables\Columns\TextColumn::make('single_file')
                    ->label('Fichier')
                    ->formatStateUsing(fn(?string $state) => $state ? 'Ouvrir' : '—')
                    ->url(fn(UploadTest $record) =>
                        $record->single_file
                        ? ($record->is_public
                            ? Storage::disk($record->disk ?? 's3')->url($record->single_file)
                            : Storage::disk($record->disk ?? 's3')->temporaryUrl($record->single_file, now()->addMinutes(5)))
                        : null
                    )
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('multiple_files')
                    ->label('Fichiers')
                   ->view('tables.columns.multiple-files'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\Action::make('verifier')
                    ->label('Vérifier sur le disque')
                    ->action(function (UploadTest $record) {
                        $disk     = $record->disk ?? 's3';
                        $okSingle = true;
                        $okMulti  = true;

                        try {
                            if ($record->single_file) {
                                $okSingle = Storage::disk($disk)->exists($record->single_file);
                            }
                            if (is_array($record->multiple_files)) {
                                foreach ($record->multiple_files as $p) {
                                    if (! Storage::disk($disk)->exists($p)) {
                                        $okMulti = false;break;
                                    }
                                }
                            }
                            if ($okSingle && $okMulti) {
                                Notification::make()
                                    ->title('Tous les fichiers existent sur le disque.')
                                    ->success()->send();
                            } else {
                                Notification::make()
                                    ->title('Fichier(s) manquant(s) sur le disque.')
                                    ->danger()->send();
                            }
                        } catch (\Throwable $e) {
                            $record->update(['error_message' => $e->getMessage()]);
                            Notification::make()
                                ->title('Erreur lors de la vérification')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    })
                    ->icon('heroicon-o-check-badge'),

                Tables\Actions\Action::make('supprimer_fichiers')
                    ->label('Supprimer fichiers')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (UploadTest $record) {
                        $disk = $record->disk ?? 's3';
                        try {
                            if ($record->single_file) {
                                Storage::disk($disk)->delete($record->single_file);
                            }
                            if (is_array($record->multiple_files)) {
                                Storage::disk($disk)->delete($record->multiple_files);
                            }
                            $record->update(['single_file' => null, 'multiple_files' => []]);
                            Notification::make()->title('Fichiers supprimés.')->success()->send();
                        } catch (\Throwable $e) {
                            $record->update(['error_message' => $e->getMessage()]);
                            Notification::make()
                                ->title('Suppression échouée')
                                ->body($e->getMessage())
                                ->danger()->send();
                        }
                    })
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                \App\Filament\Actions\TrashBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUploadTests::route('/'),
            'create' => Pages\CreateUploadTest::route('/create'),
            'edit'   => Pages\EditUploadTest::route('/{record}/edit'),
        ];
    }
}
