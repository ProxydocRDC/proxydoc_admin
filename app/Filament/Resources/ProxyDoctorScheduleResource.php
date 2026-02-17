<?php

namespace App\Filament\Resources;

use App\Filament\Actions\TrashAction;
use App\Filament\Actions\TrashBulkAction;
use App\Filament\Concerns\HasTrashableRecords;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
// use Pages\ViewProxyDoctorSchedule;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Models\ProxyDoctorSchedule;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\ProxyDoctorScheduleResource\Pages;
use Filament\Tables\Columns\{TextColumn, ToggleColumn, BadgeColumn};
use Filament\Forms\Components\{Group, Section, Hidden, Toggle, TextInput, DatePicker, Select};

class ProxyDoctorScheduleResource extends Resource
{
    use HasTrashableRecords;
    protected static ?string $model = ProxyDoctorSchedule::class;

    protected static ?string $navigationIcon  = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Proxydoc';
    protected static ?string $navigationLabel = 'Agendas médecins';
    protected static ?string $modelLabel      = 'Agenda médecin';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make('Fiche agenda')->schema([
                    Hidden::make('created_by')->default(fn () => Auth::id()),
                    Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(),

                    // Toggle::make('status')
                    //     ->label('Actif')->default(true)
                    //     ->onColor('success')->offColor('danger')
                    //     ->columnSpan(3),

                        Select::make('doctor_user_id')
                            ->label('Médecin (utilisateur)')
                            ->options(fn () => User::query()
                                ->whereHas('proxyDoctor')
                                ->select('id', DB::raw("TRIM(CONCAT(COALESCE(firstname,''),' ',COALESCE(lastname,''))) AS label"))
                                ->orderBy('label')
                                ->pluck('label', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(6),


                    TextInput::make('name')
                        ->label("Nom de l’agenda")->required()->maxLength(100)
                        ->columnSpan(6),



                    DatePicker::make('valid_from')->label('Valide à partir du')->native(false)->columnSpan(6),
                    DatePicker::make('valid_to')->label('Valide jusqu’au')->native(false)->columnSpan(6),
                Toggle::make('is_default')
                        ->label('Agenda par défaut')->default(true)
                        ->columnSpan(4),
                    ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                ToggleColumn::make('status')->label('Actif'),
                TextColumn::make('name')->label('Agenda')->searchable()->sortable(),
                TextColumn::make('doctorUser.email')->label('Médecin (user)')->searchable(),
                BadgeColumn::make('is_default')->label('Défaut')->colors([
                    'success' => fn (bool $state) => $state,
                    'gray'    => fn (bool $state) => ! $state,
                ])->formatStateUsing(fn (bool $state) => $state ? 'Oui' : 'Non'),
                TextColumn::make('valid_from')->date('Y-m-d')->label('Du'),
                TextColumn::make('valid_to')->date('Y-m-d')->label('Au'),
                TextColumn::make('created_at')->dateTime('Y-m-d H:i')->label('Créé'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([...array_filter([static::getTrashFilter()])])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                TrashAction::make(),
            ])
            ->bulkActions([TrashBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyDoctorSchedules::route('/'),
            'create' => Pages\CreateProxyDoctorSchedule::route('/create'),
            'view'   => Pages\ViewProxyDoctorSchedule::route('/{record}'),
            'edit'   => Pages\EditProxyDoctorSchedule::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id();
        $data['status']     = $data['status']     ?? 1;
        $data['is_default'] = $data['is_default'] ?? 1;
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = Auth::id();
        return $data;
    }
}
