<?php

namespace App\Providers;

use App\Models\User;
use App\Models\ProxyDoctor;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        ProxyDoctor::observe(\App\Observers\ProxyDoctorObserver::class);

        Gate::policy(
            \App\Filament\Resources\UserResource\Widgets\PatientStatsWidget::class,
            \App\Policies\Widgets\PatientStatsWidgetPolicy::class
        );
    }
}
