<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use App\Models\ProxyDoctor;
use App\Models\ProxyService;
use Illuminate\Support\Carbon;
use App\Models\ProxyAppointment;
use App\Models\ProxyDoctorService;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class TopServicesTable extends BaseWidget
{
    use HasWidgetShield;
    protected static ?string $heading = 'Top services (30 jours)';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getTableQuery(): Builder
    {
        $from = Carbon::now()->subDays(30);

        // Nombre de RDV par service sur 30 jours
        return ProxyService::query()
            ->select('proxy_services.*')
            ->withCount(['doctorServices as doctors_actifs_count' => function ($q) {
                $q->where('status', 1);
            }])
            ->withCount(['appointments as rdv_30j_count' => function ($q) use ($from) {
                $q->where('scheduled_at', '>=', $from);
            }])
            ->orderByDesc('rdv_30j_count');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('label')->label('Service')->searchable()->sortable(),
            TextColumn::make('code')->label('Code')->toggleable(),
            TextColumn::make('doctors_actifs_count')->label('Médecins actifs'),
            TextColumn::make('rdv_30j_count')->label('RDV (30j)')->sortable(),
        ];
    }
}
