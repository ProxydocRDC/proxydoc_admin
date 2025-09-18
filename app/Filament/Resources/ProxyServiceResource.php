<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\ProxyService;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\ProxyServiceResource\Pages;
use Filament\Tables\Columns\{TextColumn, ToggleColumn};
use Filament\Forms\Components\{Group, Section, Hidden, Toggle, TextInput, Textarea, FileUpload};

class ProxyServiceResource extends Resource
{
    protected static ?string $model = ProxyService::class;

    protected static ?string $navigationIcon  = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Proxydoc';
    protected static ?string $navigationLabel = 'Services';
    protected static ?string $modelLabel      = 'Service';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make('Service')->schema([
                    Hidden::make('created_by')->default(fn () => Auth::id()),
                    Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(),

                    // Toggle::make('status')
                    //     ->label('Actif')
                    //     ->onColor('success')->offColor('danger')
                    //     ->default(true)->required(),

                    TextInput::make('code')->label('Code')
                        ->required()->maxLength(50)->columnSpan(4),

                    TextInput::make('label')->label('Libellé')
                        ->required()->maxLength(100)->columnSpan(4),
                   Select::make('specialty_level')
    ->label('Niveau de spécialité')
    ->options([
        0 => 'Spécialité 0',
        1 => 'Spécialité 1',
        2 => 'Spécialité 2',
        3 => 'Spécialité 3',
        ])->default(0)          // décommente si tu veux 0 par défaut
    ->placeholder('— Sélectionner —')
    ->required()          // oblige un choix
    ->native(false)       // rendu “filament” (pas le select natif)
    ->columnSpan(4),
                    TextInput::make('chat_price')->label('Prix chat')->numeric()
                    ->numeric()
                        ->required()->maxLength(100)->columnSpan(4),
                    TextInput::make('audio_price')->label('Prix audio')->numeric()
                        ->required()->maxLength(100)->columnSpan(4),
                    TextInput::make('video_price')->label('Prix vidéo')->numeric()
                        ->required()->maxLength(100)->columnSpan(4),

                    Textarea::make('description')->label('Description')->rows(3)
                    ->columnSpan(6),

                    // FileUpload::make('image')
                    //     ->label('Illustration')
                    //     ->columnSpan(6)
                    //     ->image()->imagePreviewHeight('150')
                    //     ->directory('proxy_services')->visibility('public'),
                          FileUpload::make('image')
                            ->label('Image illustrative')
                            ->image()
                            ->imagePreviewHeight('150')
                            ->maxSize(2048)
                            ->columnSpan(6)
                            ->disk('s3') // Filament uploade direct vers S3
                            ->directory('proxyServices')
                            ->visibility('private')
                            ->maxSize(10240) // 10 Mo
                            ->openable(),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([

                  ImageColumn::make('image') // colonne réelle = 'image'
                    ->label('Image')
                    ->getStateUsing(fn($record) => $record->mediaUrl('image')) // URL finale
                    ->size(64)
                    ->square()
                    ->defaultImageUrl(asset('assets/images/default.jpg'))  // 👈 évite l’icône cassée
                    ->openUrlInNewTab()
                    ->url(fn($record) => $record->mediaUrl('image', ttl: 5)), // clic = grande image

                TextColumn::make('specialty_level')->label('Niveau de spécialité')->sortable()->searchable(),
                TextColumn::make('chat_price')->label('Prix chat')->sortable()->searchable(),
                TextColumn::make('audio_price')->label('Prix audio')->sortable()->searchable(),
                TextColumn::make('video_price')->label('Prix vidéo')->sortable()->searchable(),
                TextColumn::make('code')->label('Code')->sortable()->searchable(),
                TextColumn::make('label')->label('Libellé')->sortable()->searchable(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé')->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('status')->label('Actif'),
            ])
            ->actions([
                // Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('id','desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyServices::route('/'),
            // 'create' => Pages\CreateProxyService::route('/create'),
            // 'view'   => Pages\ViewProxyService::route('/{record}'),
            // 'edit'   => Pages\EditProxyService::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();
        $data['status']     = $data['status']     ?? 1;
        return $data;
    }
    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
