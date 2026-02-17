<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Corbeille extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-trash';
    protected static ?string $navigationLabel = 'Corbeille';
    protected static ?string $title = 'Corbeille';
    protected static ?string $navigationGroup = 'Paramètres';
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.corbeille';

    public ?string $activeModel = null;

    public static function getNavigationBadge(): ?string
    {
        $total = 0;
        foreach (array_keys(config('corbeille.models', [])) as $modelClass) {
            if (! class_exists($modelClass)) {
                continue;
            }
            $instance = new $modelClass;
            if (Schema::hasColumn($instance->getTable(), 'status')) {
                $total += $modelClass::query()->where('status', 0)->count();
            }
        }
        return $total > 0 ? (string) $total : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function mount(): void
    {
        $models = $this->getTrashableModels();
        $type = request()->query('type');
        if ($type && isset($models[$type])) {
            $this->activeModel = $type;
        } else {
            $this->activeModel = array_key_first($models) ?? null;
        }
    }

    protected function getTrashableModels(): array
    {
        $models = config('corbeille.models', []);
        $result = [];

        foreach ($models as $modelClass => $modelConfig) {
            if (! class_exists($modelClass)) {
                continue;
            }
            $instance = new $modelClass;
            $table = $instance->getTable();
            if (Schema::hasColumn($table, 'status')) {
                $result[$modelClass] = $modelConfig['label'] ?? class_basename($modelClass);
            }
        }

        return $result;
    }

    public function table(Table $table): Table
    {
        $modelClass = $this->activeModel;
        if (! $modelClass || ! class_exists($modelClass)) {
            return $table->query(\App\Models\User::query()->whereRaw('1=0'));
        }

        $config = config('corbeille.models', [])[$modelClass] ?? ['columns' => ['id', 'created_at']];
        $columns = $config['columns'] ?? ['id', 'created_at'];

        $instance = new $modelClass;
        $tableName = $instance->getTable();
        $query = $modelClass::query()->where($tableName . '.status', 0);

        $columnDefs = [];
        foreach ($columns as $col) {
            $columnDefs[] = Tables\Columns\TextColumn::make($col)
                ->label(str_replace('_', ' ', ucfirst($col)))
                ->searchable()
                ->sortable();
        }

        if (! in_array('id', $columns)) {
            array_unshift($columnDefs, Tables\Columns\TextColumn::make('id')->label('ID')->sortable());
        }

        return $table
            ->query($query)
            ->columns($columnDefs)
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('restore')
                    ->label('Restaurer')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restaurer')
                    ->modalDescription('L\'élément sera restauré (statut actif).')
                    ->action(function (Model $record): void {
                        $record->update(['status' => 1]);
                        Notification::make()
                            ->title('Restauré')
                            ->body('L\'enregistrement a été restauré.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('forceDelete')
                    ->label('Supprimer définitivement')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer définitivement ?')
                    ->modalDescription('Opération irréversible. L\'enregistrement sera supprimé de la base de données.')
                    ->visible(fn (): bool => Auth::user()?->hasRole('super_admin') ?? false)
                    ->action(function (Model $record): void {
                        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
                            $record->forceDelete();
                        } else {
                            $record->delete();
                        }
                        Notification::make()
                            ->title('Supprimé définitivement')
                            ->body('L\'enregistrement a été supprimé.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('restoreBulk')
                    ->label('Restaurer la sélection')
                    ->icon('heroicon-m-arrow-uturn-left')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        foreach ($records as $record) {
                            if (Schema::hasColumn($record->getTable(), 'status')) {
                                $record->update(['status' => 1]);
                            }
                        }
                        Notification::make()
                            ->title('Restaurés')
                            ->body($records->count() . ' enregistrement(s) restauré(s).')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\BulkAction::make('forceDeleteBulk')
                    ->label('Supprimer définitivement')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer définitivement ?')
                    ->modalDescription('Opération irréversible. Les enregistrements seront supprimés.')
                    ->visible(fn (): bool => Auth::user()?->hasRole('super_admin') ?? false)
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                        foreach ($records as $record) {
                            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($record))) {
                                $record->forceDelete();
                            } else {
                                $record->delete();
                            }
                        }
                        Notification::make()
                            ->title('Supprimés')
                            ->body($records->count() . ' enregistrement(s) supprimé(s) définitivement.')
                            ->success()
                            ->send();
                    }),
            ]);
    }


    protected function getHeaderActions(): array
    {
        return [];
    }
}
