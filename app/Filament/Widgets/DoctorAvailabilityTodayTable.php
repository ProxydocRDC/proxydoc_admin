<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\ProxyDoctorAvailability;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;

class DoctorAvailabilityTodayTable extends BaseWidget
{
    protected static ?string $heading = 'Disponibilités du jour';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getTableQuery(): Builder
    {
        $todayWeekday = (int) Carbon::now()->isoWeekday(); // 1=Lundi … 7=Dimanche
        return ProxyDoctorAvailability::query()
            ->with(['schedule.doctor', 'schedule.doctorUser'])
            ->where('weekday', $todayWeekday)
            ->where('status', 1)
            ->orderBy('start_time');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('schedule.doctor.fullname')
                ->label('Médecin')
                ->formatStateUsing(function ($state, $record) {
                    return $record->schedule?->doctor?->fullname
                        ?? $record->schedule?->doctorUser?->fullname
                        ?? $record->schedule?->doctorUser?->email
                        ?? '—';
                })
                ->searchable(),

            TextColumn::make('schedule.name')->label('Agenda')->searchable(),
            TextColumn::make('start_time')->time('H:i')->label('Début'),
            TextColumn::make('end_time')->time('H:i')->label('Fin'),
            TextColumn::make('slot_duration')->label('Créneau')->suffix(' min'),
            TextColumn::make('methods')->label('Modes')->formatStateUsing(function ($state) {
                $arr = is_string($state) ? explode(',', $state) : (array) $state;
                return implode(', ', array_map(fn ($v) => ucfirst($v), $arr));
            }),
        ];
    }
}
