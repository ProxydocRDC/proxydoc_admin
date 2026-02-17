<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use App\Filament\Resources\ProxyDoctorServiceResource\Pages;
use App\Models\ProxyDoctorService;
use App\Models\ProxyDoctor;
use Filament\Forms;
use Filament\Forms\Components\{Group, Section, Hidden, Toggle, Select};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn, ToggleColumn};
use Illuminate\Support\Facades\Auth;

class ProxyDoctorServiceResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = ProxyDoctorService::class;

    protected static ?string $navigationIcon  = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Proxydoc';
    protected static ?string $navigationLabel = 'Médecin ↔ Service';
    protected static ?string $modelLabel      = 'Affectation médecin/service';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make('Affectation')->schema([
                    Hidden::make('created_by')->default(fn () => Auth::id()),
                    Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(),

                    Toggle::make('status')->label('Actif')
                        ->onColor('success')->offColor('danger')
                        ->default(true)->required(),

                    // doctor_user_id référence proxy_doctors.user_id (FK)
                    // On utilise la relation 'doctor' (belongsTo ProxyDoctor via ownerKey user_id)
                    Select::make('doctor_user_id')
                        ->label('Médecin')
                        ->relationship('doctor', 'fullname') // affiche ProxyDoctor.fullname et stocke user_id
                        ->getOptionLabelFromRecordUsing(function (ProxyDoctor $doc): string {
                            return $doc->fullname;
                        })
//                         ->getOptionLabelFromRecordUsing(
//     fn (\App\Models\ProxyDoctor $record): string => $record->fullname
// )
                        ->searchable()
                        ->preload()
                        ->required(),

                    Select::make('service_id')
                        ->label('Service')
                        ->relationship('service', 'label')
                        ->searchable()->preload()->required(),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('doctor.fullname')->label('Médecin')->searchable(),
                TextColumn::make('service.label')->label('Service')->searchable(),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('service_id')->label('Service')
                    ->relationship('service', 'label'),
                Tables\Filters\SelectFilter::make('doctor_user_id')->label('Médecin')
                    ->options(fn () => \App\Models\ProxyDoctor::query()
                        ->orderBy('fullname')
                        ->pluck('fullname','user_id')->toArray()
                    ),
                ...array_filter([static::getTrashFilter()]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                TrashAction::make(),
            ])
            ->defaultSort('id','desc')
            ->bulkActions([TrashBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyDoctorServices::route('/'),
            'create' => Pages\CreateProxyDoctorService::route('/create'),
            'view'   => Pages\ViewProxyDoctorService::route('/{record}'),
            'edit'   => Pages\EditProxyDoctorService::route('/{record}/edit'),
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
