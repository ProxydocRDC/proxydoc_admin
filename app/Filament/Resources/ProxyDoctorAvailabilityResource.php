<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProxyDoctorAvailabilityResource\Pages;
use App\Models\ProxyDoctorAvailability;
use Filament\Forms;
use App\Models\ProxyDoctorSchedule;
use Filament\Forms\Components\{
    Group, Section, Hidden, Toggle, Select, TimePicker, TextInput
};
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\{TextColumn, ToggleColumn, BadgeColumn};
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use App\Models\User;
use Filament\Forms\Components\Select as FormSelect;
use Filament\Tables\Enums\FiltersLayout;
class ProxyDoctorAvailabilityResource extends Resource
{
    protected static ?string $model = ProxyDoctorAvailability::class;

    protected static ?string $navigationIcon  = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Proxydoc';
    protected static ?string $navigationLabel = 'Disponibilités';
    protected static ?string $modelLabel      = 'Disponibilité';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Group::make([
                Section::make('Créneau disponible')->schema([
                    Hidden::make('created_by')->default(fn () => Auth::id()),
                    Hidden::make('updated_by')->default(fn () => Auth::id())->dehydrated(),


                    // Select::make('schedule_id')
                    //     ->label('Agenda')
                    //     ->relationship('schedule', 'name')
                    //     ->searchable()->preload()->required()
                    //     ->columnSpan(4),

                        Select::make('schedule_id')
                            ->label('Agenda')
                            ->relationship('schedule', 'name')     // on garde la relation
                            ->getOptionLabelFromRecordUsing(
                                fn (ProxyDoctorSchedule $rec) =>
                                    sprintf(
                                        '%s — %s',
                                        ($rec->doctor?->fullname    // ProxyDoctor.fullname si présent
                                        ?? $rec->doctorUser?->fullname // sinon User.firstname/lastname
                                        ?? $rec->doctorUser?->email    // sinon l’email
                                        ?? '—'),
                                        $rec->name
                                    )
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(12),

                    Select::make('weekday')
                        ->label('Jour')
                        ->options([
                            1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi',
                            5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
                        ])
                        ->required()->columnSpan(4),

                    TimePicker::make('start_time')->label('Début')->seconds(false)->required()->columnSpan(4),
                    TimePicker::make('end_time')->label('Fin')->seconds(false)->required()->columnSpan(4),

                    TextInput::make('slot_duration')->label('Durée (min)')
                        ->numeric()->minValue(5)->maxValue(480)->required()->columnSpan(4),

                    // methods = SET('chat','audio','video') -> multi-select avec conversion Array<->String
                    Select::make('methods')
                        ->label('Modes')
                        ->multiple()
                        ->options([
                            'chat'  => 'Chat',
                            'audio' => 'Audio',
                            'video' => 'Vidéo',
                        ])
                        // String -> Array quand on hydrate
                        ->afterStateHydrated(function ($state, Forms\Set $set) {
                            if (is_string($state)) $set('methods', explode(',', $state));
                        })
                        // Array -> String avant sauvegarde (MySQL SET)
                        ->dehydrateStateUsing(fn ($state) => $state ? implode(',', $state) : null)
                        ->columnSpan(8),
                ])->columns(12),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        // return $table
        //     ->columns([
        //         TextColumn::make('schedule.name')->label('Agenda')->searchable(),
        //         BadgeColumn::make('weekday')->label('Jour')
        //             ->formatStateUsing(fn (int $state) => [
        //                 1=>'Lun',2=>'Mar',3=>'Mer',4=>'Jeu',5=>'Ven',6=>'Sam',7=>'Dim'
        //             ][$state] ?? (string) $state),
        //         TextColumn::make('start_time')->time('H:i')->label('Début'),
        //         TextColumn::make('end_time')->time('H:i')->label('Fin'),
        //         BadgeColumn::make('methods')->label('Modes')
        //             ->formatStateUsing(function ($state) {
        //                 $arr = is_string($state) ? explode(',', $state) : ((array) $state);
        //                 return implode(', ', array_map(fn ($v) => ucfirst($v), $arr));
        //             }),
        //         TextColumn::make('slot_duration')->label('Durée')->suffix(' min'),
        //     ])
        //     ->defaultSort('id', 'desc')
         return $table
        ->columns([
            Split::make([
                // Colonne gauche : Médecin + Agenda + Jour
                Stack::make([
                    TextColumn::make('doctor_label')
                        ->label('Médecin')
                        ->state(fn ($record) =>
                            $record->schedule?->doctor?->fullname
                            ?? $record->schedule?->doctorUser?->fullname
                            ?? $record->schedule?->doctorUser?->email
                            ?? '—'
                        )
                        ->weight('bold')
                        ->wrap(),

                    TextColumn::make('schedule.name')
                        ->label('Agenda')
                        ->color('gray')
                        ->wrap(),

                    BadgeColumn::make('weekday')
                        ->label('Jour')
                        ->formatStateUsing(fn (int $state) => [
                            1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',
                            5=>'Vendredi',6=>'Samedi',7=>'Dimanche',
                        ][$state] ?? (string) $state),
                ])->space(1),

                // Colonne droite : Heures + Durée + Modes
                Stack::make([
                    TextColumn::make('hours')
                        ->label('Heures')
                        ->state(fn ($record) => sprintf(
                            '%s – %s',
                            optional($record->start_time)->format('H:i'),
                            optional($record->end_time)->format('H:i'),
                        )),

                    TextColumn::make('slot_duration')
                        ->label('Créneau')
                        ->suffix(' min'),

                    BadgeColumn::make('methods')
                        ->label('Modes')
                        ->formatStateUsing(function ($state) {
                            $arr = is_string($state) ? explode(',', $state) : (array) $state;
                            return implode(', ', array_map(fn ($v) => ucfirst($v), $arr));
                        }),
                ])->alignment('right'),

            ])->from('md'), // le groupement s’applique à partir du breakpoint md
        ]) ->filters([
            // 1) JOURS (multi)
            SelectFilter::make('weekday')
                ->label('Jour')
                ->multiple()
                ->options([
                    1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi',
                    4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche',
                ])->columnSpan(3),

            // 2) PROGRAMMES / AGENDAS (affiche "Docteur — Agenda")
            SelectFilter::make('schedule_id')
                ->label('Agenda')
                ->options(function () {
                    return ProxyDoctorSchedule::query()
                        ->with(['doctor','doctorUser'])
                        ->get()
                        ->mapWithKeys(function ($s) {
                            $doc = $s->doctor?->fullname
                                ?? $s->doctorUser?->fullname
                                ?? $s->doctorUser?->email
                                ?? '—';
                            return [$s->id => "{$doc} — {$s->name}"];
                        })->toArray();
                })->columnSpan(3)
                ->searchable(),

            // 3) MÉDECINS (on filtre via schedule.doctor_user_id)
            Filter::make('doctor')
                ->label('Médecin')
                ->form([
                    FormSelect::make('doctor_user_id')
                        ->label('Médecin')
                        ->options(fn () => User::query()
                            ->whereHas('proxyDoctor')
                            ->get()
                            ->mapWithKeys(fn ($u) => [$u->id => ($u->fullname ?? $u->email)])
                            ->toArray()
                        )
                        ->searchable()
                        ->native(false),
                ])
                ->query(function ($query, array $data) {
                    $id = $data['doctor_user_id'] ?? null;
                    if ($id) {
                        $query->whereHas('schedule', fn ($q) => $q->where('doctor_user_id', $id));
                    }
                })
                ->indicateUsing(function (array $data) {
                    if (! ($id = $data['doctor_user_id'] ?? null)) return null;
                    $u = User::find($id);
                    return $u ? ['Médecin: ' . ($u->fullname ?? $u->email)] : null;
                })->columnSpan(6),

            // 4) HEURES (intervalle : on garde les créneaux qui chevauchent la plage)
            Filter::make('hour_range')
                ->label('Heure')
                ->form([
                    TimePicker::make('from')->label('De')->seconds(false),
                    TimePicker::make('to')->label('À')->seconds(false),
                ])
                ->query(function ($query, array $data) {
                    $from = $data['from'] ?? null;
                    $to   = $data['to'] ?? null;

                    // chevauchement: start_time <= to ET end_time >= from
                    if ($from && $to) {
                        $query->where('start_time', '<=', $to)
                              ->where('end_time',   '>=', $from);
                    } elseif ($from) {
                        $query->where('end_time', '>=', $from);
                    } elseif ($to) {
                        $query->where('start_time', '<=', $to);
                    }
                })
                ->indicateUsing(function (array $data) {
                    $from = $data['from'] ?? null;
                    $to   = $data['to'] ?? null;
                    return $from || $to ? [sprintf('Heure: %s–%s', $from ?? '…', $to ?? '…')] : null;
                })->columnSpan(6),

            // 5) TEMPS = durée de créneau (min/max en minutes)
            Filter::make('duration')
                ->label('Durée (min)')
                ->form([
                    TextInput::make('min')->numeric()->label('Min'),
                    TextInput::make('max')->numeric()->label('Max'),
                ])
                ->query(function ($query, array $data) {
                    $min = $data['min'] ?? null;
                    $max = $data['max'] ?? null;
                    if ($min !== null && $min !== '') $query->where('slot_duration', '>=', (int) $min);
                    if ($max !== null && $max !== '') $query->where('slot_duration', '<=', (int) $max);
                })
                ->indicateUsing(function (array $data) {
                    $min = $data['min'] ?? null;
                    $max = $data['max'] ?? null;
                    return ($min !== null && $min !== '') || ($max !== null && $max !== '')
                        ? [sprintf('Durée: %s–%s min', $min ?? '…', $max ?? '…')]
                        : null;
                })->columnSpan(6),

            // 6) MÉTHODES (chat/audio/video) — SET "chat,audio,video"
            Filter::make('methods')
                ->label('Méthodes')
                ->form([
                    FormSelect::make('values')
                        ->label('Méthodes')
                        ->multiple()
                        ->options([
                            'chat'  => 'Chat',
                            'audio' => 'Audio',
                            'video' => 'Vidéo',
                        ])
                        ->native(false),
                    Toggle::make('match_all')->label('Doit contenir TOUTES'),
                ])
                ->query(function ($query, array $data) {
                    $values = $data['values'] ?? [];
                    if (! $values) return;

                    $matchAll = (bool) ($data['match_all'] ?? false);

                    $query->where(function ($q) use ($values, $matchAll) {
                        foreach ($values as $m) {
                            if ($matchAll) {
                                // SET (string "a,b"): exiger toutes les valeurs sélectionnées
                                $q->whereRaw('FIND_IN_SET(?, methods)', [$m]);
                                // Si JSON: remplacer par ->whereJsonContains('methods', $m);
                            } else {
                                // au moins une méthode
                                $q->orWhereRaw('FIND_IN_SET(?, methods)', [$m]);
                                // Si JSON: remplacer par ->orWhereJsonContains('methods', $m);
                            }
                        }
                    });
                })->columnSpan(6)
                ->indicateUsing(function (array $data) {
                    $values = $data['values'] ?? [];
                    if (! $values) return null;
                    $label = implode(', ', array_map('ucfirst', $values));
                    return [($data['match_all'] ?? false ? 'Méthodes (toutes): ' : 'Méthodes: ') . $label];
                })->columnSpan(12),
        ])->filtersLayout(FiltersLayout::AboveContent)   // ⬅️ important
        ->filtersFormColumns(12)                        // (optionnel) grille des filtres
        ->persistFiltersInSession()                  // (optionnel) se souvenir des filtres
        // ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent) // si tu veux les mettre en haut

        ->defaultSort('weekday')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProxyDoctorAvailabilities::route('/'),
            'create' => Pages\CreateProxyDoctorAvailability::route('/create'),
            'view'   => Pages\ViewProxyDoctorAvailability::route('/{record}'),
            'edit'   => Pages\EditProxyDoctorAvailability::route('/{record}/edit'),
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
