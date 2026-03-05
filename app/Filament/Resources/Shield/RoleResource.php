<?php

namespace App\Filament\Resources\Shield;

use BezhanSalleh\FilamentShield\Resources\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Forms;
use Filament\Forms\Form;
use Illuminate\Support\HtmlString;

class RoleResource extends ShieldRoleResource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => Utils::isTenancyEnabled() ? $rule->where(Utils::getTenantModelForeignKey(), \Filament\Facades\Filament::getTenant()?->id) : $rule)
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Forms\Components\Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    ->default([\Filament\Facades\Filament::getTenant()?->id])
                                    ->options(fn (): \Illuminate\Contracts\Support\Arrayable => Utils::getTenantModel() ? Utils::getTenantModel()::pluck('name', 'id') : collect())
                                    ->hidden(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled()))
                                    ->dehydrated(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled())),

                                \BezhanSalleh\FilamentShield\Forms\ShieldSelectAllToggle::make('select_all')
                                    ->onIcon('heroicon-s-shield-check')
                                    ->offIcon('heroicon-s-shield-exclamation')
                                    ->label(__('filament-shield::filament-shield.field.select_all.name'))
                                    ->helperText(fn (): HtmlString => new HtmlString(__('filament-shield::filament-shield.field.select_all.message')))
                                    ->dehydrated(fn (bool $state): bool => $state),
                            ])
                            ->columns(['sm' => 2, 'lg' => 3]),
                    ]),
                static::getShieldFormComponentsWithHelp(),
            ]);
    }

    protected static function getShieldFormComponentsWithHelp(): Forms\Components\Component
    {
        $tabs = [
            static::getTabWithHelp('resources', __('filament-shield::filament-shield.resources'), 'resources_help', static::getTabFormComponentForResources()),
            static::getTabWithHelp('pages', __('filament-shield::filament-shield.pages'), 'pages_help', static::getTabFormComponentForPage()),
            static::getTabWithHelp('widgets', __('filament-shield::filament-shield.widgets'), 'widgets_help', static::getTabFormComponentForWidget()),
            static::getTabWithHelp('custom', __('filament-shield::filament-shield.custom'), 'custom_help', static::getTabFormComponentForCustomPermissions()),
        ];

        return Forms\Components\Tabs::make('Permissions')
            ->contained()
            ->tabs(array_filter($tabs))
            ->columnSpan('full');
    }

    protected static function getTabWithHelp(string $name, string $label, string $helpKey, Forms\Components\Tabs\Tab $tab): Forms\Components\Tabs\Tab
    {
        $help = __("filament-shield::filament-shield.{$helpKey}");
        if ($help !== "filament-shield::filament-shield.{$helpKey}") {
            $tab->description($help);
        }
        return $tab;
    }
}
