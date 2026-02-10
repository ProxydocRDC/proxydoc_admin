<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\ChemProduct;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Widgets\TableWidget as BaseWidget;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ProductEncodersTable extends BaseWidget
{
    use HasWidgetShield;
    
    protected static ?string $heading = 'Utilisateurs ayant encodé des produits';
    protected int|string|array $columnSpan = 'full';
    
    // Désactiver le lazy loading pour que le widget se mette à jour correctement
    protected static bool $isLazy = false;
    
    public ?int $modalUserId = null;
    public int $modalPage = 1;
    public int $missingAuthorsModalPage = 1;
    
    public function changeModalPage(int $page): void
    {
        $this->modalPage = max(1, $page);
        // Forcer le re-rendu du widget
        $this->dispatch('$refresh');
    }

    public function getModalContent($record)
    {
        // Utiliser l'ID stocké ou celui du record
        $userId = $this->modalUserId ?? $record->id;
        $currentPage = $this->modalPage ?? 1;
        $perPage = 25;
        
        // Réinitialiser la page si l'utilisateur change
        if ($this->modalUserId !== $record->id) {
            $this->modalUserId = $record->id;
            $this->modalPage = 1;
            $currentPage = 1;
        }
        
        // S'assurer que modalPage est initialisé
        if (!isset($this->modalPage) || $this->modalPage < 1) {
            $this->modalPage = 1;
            $currentPage = 1;
        }
        
        // Récupérer les filtres de date depuis les filtres actifs
        $dateFrom = null;
        $dateTo = null;
        try {
            $dateFilterState = $this->getTableFilterState('date_range');
            if (is_array($dateFilterState)) {
                $dateFrom = $dateFilterState['from'] ?? null;
                $dateTo = $dateFilterState['to'] ?? null;
                
                // Convertir les dates si elles sont des strings
                if ($dateFrom && is_string($dateFrom)) {
                    $dateFrom = Carbon::parse($dateFrom);
                }
                if ($dateTo && is_string($dateTo)) {
                    $dateTo = Carbon::parse($dateTo);
                }
            }
        } catch (\Exception $e) {
            $dateFrom = null;
            $dateTo = null;
        }

        // Générer une clé de cache unique basée sur les filtres avec version
        $cacheVersion = Cache::get('products_filter_cache_version', 1);
        $dateFromStr = $dateFrom ? $dateFrom->format('Y-m-d') : 'all';
        $dateToStr = $dateTo ? $dateTo->format('Y-m-d') : 'all';
        $cacheKey = 'products_modal_' . $userId . '_' . $dateFromStr . '_' . $dateToStr . '_v' . $cacheVersion;
        
        // Construire la requête de base pour compter le total (avec cache de 2 minutes)
        $total = Cache::remember($cacheKey . '_count', 120, function () use ($userId, $dateFrom, $dateTo) {
            $baseQuery = ChemProduct::query()->where('created_by', $userId);
            
            if ($dateFrom) {
                $baseQuery->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $baseQuery->whereDate('created_at', '<=', $dateTo);
            }
            
            return $baseQuery->count();
        });

        if ($total === 0) {
            $html = '<div class="flex flex-col items-center justify-center p-8 text-center">';
            $html .= '<svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
            $html .= '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>';
            $html .= '</svg>';
            $html .= '<h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-2">Aucun produit</h3>';
            $html .= '<p class="text-gray-500 dark:text-gray-400">Cet utilisateur n\'a pas encodé de produits' . ($dateFrom || $dateTo ? ' dans cette période' : '') . '.</p>';
            $html .= '</div>';
            return new \Illuminate\Support\HtmlString($html);
        }

        // Pagination côté base de données - charger seulement les 25 produits de la page actuelle (avec cache de 2 minutes)
        $offset = ($currentPage - 1) * $perPage;
        $pageCacheKey = $cacheKey . '_page_' . $currentPage;
        
        $products = Cache::remember($pageCacheKey, 120, function () use ($userId, $dateFrom, $dateTo, $offset, $perPage) {
            $query = ChemProduct::query()
                ->where('created_by', $userId)
                ->with(['category', 'manufacturer', 'form', 'creator', 'updater'])
                ->orderByDesc('created_at');
            
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
            
            return $query->skip($offset)->take($perPage)->get();
        });

        // Utiliser la vue personnalisée avec pagination - ajouter wire:key pour forcer le re-rendu
        return view('filament.widgets.products-modal', [
            'products' => $products,
            'total' => $total,
            'perPage' => $perPage,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'userId' => $userId,
            'currentPage' => $currentPage,
            'widgetId' => $this->getId(),
        ]);
    }
    
    public function getTableFilterState(?string $name = null): ?array
    {
        return parent::getTableFilterState($name);
    }

    protected function clearFilterCache(): void
    {
        // Incrémenter la version du cache pour invalider tous les anciens caches
        $cacheVersion = Cache::get('products_filter_cache_version', 1);
        Cache::put('products_filter_cache_version', $cacheVersion + 1, 86400);
        
        // Les clés de cache incluront maintenant cette version pour invalidation automatique
        // Cela permet de ne pas avoir à nettoyer manuellement chaque clé
    }

    public static function canView(): bool
    {
        return Auth::check();
    }

    protected function getTableDescription(): ?string
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        [$dateFrom, $dateTo] = $this->getPeriodDates();

        $fromLabel = $dateFrom ? $dateFrom->format('d/m/Y') : '—';
        $toLabel = $dateTo ? $dateTo->format('d/m/Y') : '—';

        return new \Illuminate\Support\HtmlString(
            '<div class="space-y-1 text-sm text-gray-500">' .
                '<div>Semaine : ' . $weekStart->format('d/m/Y') . ' → ' . $weekEnd->format('d/m/Y') . '</div>' .
                '<div>Mois : ' . $monthStart->format('d/m/Y') . ' → ' . $monthEnd->format('d/m/Y') . '</div>' .
                '<div>Période : ' . $fromLabel . ' → ' . $toLabel . '</div>' .
            '</div>'
        );
    }

    protected function getPeriodDates(): array
    {
        $dateFrom = null;
        $dateTo = null;

        try {
            $dateFilterState = $this->getTableFilterState('date_range');
            if (is_array($dateFilterState)) {
                $dateFrom = $dateFilterState['from'] ?? null;
                $dateTo = $dateFilterState['to'] ?? null;

                if ($dateFrom && is_string($dateFrom)) {
                    $dateFrom = Carbon::parse($dateFrom)->startOfDay();
                }
                if ($dateTo && is_string($dateTo)) {
                    $dateTo = Carbon::parse($dateTo)->endOfDay();
                }
            }
        } catch (\Exception $e) {
            $dateFrom = null;
            $dateTo = null;
        }

        return [$dateFrom, $dateTo];
    }

    protected function countMissingAuthors(?Carbon $dateFrom = null, ?Carbon $dateTo = null): int
    {
        $query = ChemProduct::query()
            ->where(function (Builder $q) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', 0)
                    ->orWhereNull('updated_by')
                    ->orWhere('updated_by', 0);
            });

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query->count();
    }

    protected function getTableContentFooter(): ?\Illuminate\Contracts\View\View
    {
        $weekStart = Carbon::now()->startOfWeek();
        $weekEnd = Carbon::now()->endOfWeek();
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd = Carbon::now()->endOfMonth();
        [$dateFrom, $dateTo] = $this->getPeriodDates();

        return view('filament.widgets.product-encoders-missing-authors-footer', [
            'missingAuthorsWeek' => $this->countMissingAuthors($weekStart, $weekEnd),
            'missingAuthorsMonth' => $this->countMissingAuthors($monthStart, $monthEnd),
            'missingAuthorsPeriod' => $this->countMissingAuthors($dateFrom, $dateTo),
            'missingAuthorsTotal' => $this->countMissingAuthors(),
            'missingAuthorsModalId' => 'missing-authors-modal',
        ]);
    }

    public function openMissingAuthorsModal(): void
    {
        $this->missingAuthorsModalPage = 1;
        $this->dispatch('open-modal', id: 'missing-authors-modal');
    }

    public function changeMissingAuthorsPage(int $page): void
    {
        $this->missingAuthorsModalPage = max(1, $page);
        $this->dispatch('$refresh');
    }

    public function getMissingAuthorsModalData(): array
    {
        $perPage = 25;
        $currentPage = max(1, (int) $this->missingAuthorsModalPage);
        $offset = ($currentPage - 1) * $perPage;

        $query = ChemProduct::query()
            ->where(function (Builder $q) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', 0)
                    ->orWhereNull('updated_by')
                    ->orWhere('updated_by', 0);
            })
            ->with(['category', 'manufacturer', 'form', 'creator', 'updater'])
            ->orderByDesc('created_at');

        $total = (clone $query)->count();
        $products = $query->skip($offset)->take($perPage)->get();

        return [
            'products' => $products,
            'total' => $total,
            'perPage' => $perPage,
            'dateFrom' => null,
            'dateTo' => null,
            'userId' => 'missing-authors',
            'currentPage' => $currentPage,
            'widgetId' => $this->getId(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        // Début de la semaine en cours (lundi)
        $weekStart = Carbon::now()->startOfWeek();
        // Début du mois en cours
        $monthStart = Carbon::now()->startOfMonth();

        // Obtenir le nom de la table depuis le modèle
        $productsTable = (new ChemProduct())->getTable();
        $usersTable = (new User())->getTable();

        // Requête qui groupe par utilisateur et compte les produits
        return User::query()
            ->select([
                "{$usersTable}.id",
                "{$usersTable}.firstname",
                "{$usersTable}.lastname",
                "{$usersTable}.email",
                DB::raw("COUNT(DISTINCT {$productsTable}.id) as total_products"),
                DB::raw("COUNT(DISTINCT CASE 
                    WHEN {$productsTable}.created_at >= '{$weekStart->toDateTimeString()}' THEN {$productsTable}.id 
                    ELSE NULL 
                END) as products_this_week"),
                DB::raw("COUNT(DISTINCT CASE 
                    WHEN {$productsTable}.created_at >= '{$monthStart->toDateTimeString()}' THEN {$productsTable}.id 
                    ELSE NULL 
                END) as products_this_month"),
                // Compte des produits dans la période filtrée (sera calculé dynamiquement via les filtres)
                DB::raw("COUNT(DISTINCT {$productsTable}.id) as products_in_period")
            ])
            ->join($productsTable, "{$productsTable}.created_by", '=', "{$usersTable}.id")
            ->whereNotNull("{$productsTable}.created_by")
            ->groupBy("{$usersTable}.id", "{$usersTable}.firstname", "{$usersTable}.lastname", "{$usersTable}.email")
            ->havingRaw("COUNT(DISTINCT {$productsTable}.id) > 0")
            ->orderByDesc('total_products');
    }

    // 🔑 Fournir une clé unique pour chaque ligne du tableau
    public function getTableRecordKey($record): string
    {
        return (string) ($record->id ?? uniqid('row_', true));
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('firstname')
                ->label('Prénom')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('lastname')
                ->label('Nom')
                ->searchable()
                ->sortable(),
            
            TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->toggleable(),
            
            TextColumn::make('products_this_week')
                ->label('Cette semaine')
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'success' : 'gray'),
            
            TextColumn::make('products_this_month')
                ->label('Ce mois')
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'primary' : 'gray'),
            
            TextColumn::make('products_in_period')
                ->label('Dans la période')
                ->numeric()
                ->sortable()
                ->badge()
                ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                ->getStateUsing(function ($record) {
                    // Compter les produits dans la période filtrée avec cache
                    // Ne pas utiliser de cache pour cette colonne car elle doit se mettre à jour en temps réel
                    try {
                        $dateFilterState = $this->getTableFilterState('date_range');
                        $dateFrom = null;
                        $dateTo = null;
                        
                        if (is_array($dateFilterState)) {
                            $dateFrom = $dateFilterState['from'] ?? null;
                            $dateTo = $dateFilterState['to'] ?? null;
                            
                            if ($dateFrom && is_string($dateFrom)) {
                                $dateFrom = Carbon::parse($dateFrom);
                            }
                            if ($dateTo && is_string($dateTo)) {
                                $dateTo = Carbon::parse($dateTo);
                            }
                        }
                        
                        // Calculer directement sans cache pour une mise à jour en temps réel
                        $query = ChemProduct::query()->where('created_by', $record->id);
                        
                        if ($dateFrom) {
                            $query->whereDate('created_at', '>=', $dateFrom);
                        }
                        if ($dateTo) {
                            $query->whereDate('created_at', '<=', $dateTo);
                        }
                        
                        return $query->count();
                    } catch (\Exception $e) {
                        // Si les filtres ne sont pas encore disponibles, retourner le total
                        return ChemProduct::query()->where('created_by', $record->id)->count();
                    }
                })
                ->default('—'),
            
            TextColumn::make('total_products')
                ->label('Total')
                ->numeric()
                ->sortable()
                ->badge()
                ->color('info'),
        ];
    }

    protected function getTableFilters(): array
    {
        return [
            Filter::make('date_range')
                ->label('Période (pour le décompte)')
                ->form([
                    DatePicker::make('from')
                        ->label('Du')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->reactive()
                        ->afterStateUpdated(function () {
                            // Invalider le cache quand la date change
                            $this->clearFilterCache();
                            // Forcer le rafraîchissement complet du widget
                            $this->dispatch('$refresh');
                        })
                        ->dehydrated(),
                    DatePicker::make('to')
                        ->label('Au')
                        ->displayFormat('d/m/Y')
                        ->native(false)
                        ->reactive()
                        ->afterStateUpdated(function () {
                            // Invalider le cache quand la date change
                            $this->clearFilterCache();
                            // Forcer le rafraîchissement complet du widget
                            $this->dispatch('$refresh');
                        })
                        ->dehydrated(),
                ])
                // Le filtre modifie la requête pour ne montrer que les utilisateurs ayant encodé dans la période
                ->query(function (Builder $query, array $data): Builder {
                    $dateFrom = $data['from'] ?? null;
                    $dateTo = $data['to'] ?? null;
                    
                    // Si des dates sont sélectionnées, filtrer les utilisateurs
                    if ($dateFrom || $dateTo) {
                        $productsTable = (new ChemProduct())->getTable();
                        $usersTable = (new User())->getTable();
                        
                        // Convertir les dates si elles sont des strings
                        if ($dateFrom && is_string($dateFrom)) {
                            $dateFrom = Carbon::parse($dateFrom)->startOfDay();
                        }
                        if ($dateTo && is_string($dateTo)) {
                            $dateTo = Carbon::parse($dateTo)->endOfDay();
                        }
                        
                        // Filtrer pour ne garder que les utilisateurs ayant encodé dans la période
                        // Utiliser une sous-requête pour vérifier l'existence de produits dans la période
                        $query->whereExists(function ($subQuery) use ($productsTable, $usersTable, $dateFrom, $dateTo) {
                            $subQuery->select(DB::raw(1))
                                ->from($productsTable)
                                ->whereColumn("{$productsTable}.created_by", "{$usersTable}.id")
                                ->whereNotNull("{$productsTable}.created_by");
                            
                            if ($dateFrom) {
                                $subQuery->where("{$productsTable}.created_at", '>=', $dateFrom);
                            }
                            if ($dateTo) {
                                $subQuery->where("{$productsTable}.created_at", '<=', $dateTo);
                            }
                        });
                    }
                    
                    return $query;
                }),
            TernaryFilter::make('missing_author')
                ->label('Auteur manquant')
                ->trueLabel('Manquant')
                ->falseLabel('Complet')
                ->queries(
                    true: fn (Builder $query) => $query->whereExists(function ($subQuery) {
                        $productsTable = (new ChemProduct())->getTable();
                        $usersTable = (new User())->getTable();
                        $subQuery->select(DB::raw(1))
                            ->from($productsTable)
                            ->whereColumn("{$productsTable}.created_by", "{$usersTable}.id")
                            ->where(function ($q) use ($productsTable) {
                                $q->whereNull("{$productsTable}.created_by")
                                    ->orWhere("{$productsTable}.created_by", 0)
                                    ->orWhereNull("{$productsTable}.updated_by")
                                    ->orWhere("{$productsTable}.updated_by", 0);
                            });
                    }),
                    false: fn (Builder $query) => $query->whereExists(function ($subQuery) {
                        $productsTable = (new ChemProduct())->getTable();
                        $usersTable = (new User())->getTable();
                        $subQuery->select(DB::raw(1))
                            ->from($productsTable)
                            ->whereColumn("{$productsTable}.created_by", "{$usersTable}.id")
                            ->whereNotNull("{$productsTable}.created_by")
                            ->where("{$productsTable}.created_by", '!=', 0)
                            ->whereNotNull("{$productsTable}.updated_by")
                            ->where("{$productsTable}.updated_by", '!=', 0);
                    }),
                    blank: fn (Builder $query) => $query
                )
                ->indicator('Auteur manquant'),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            Action::make('viewProducts')
                ->label('Voir les produits')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->button()
                ->outlined()
                ->modalHeading(fn ($record) => 'Produits encodés par ' . ($record->firstname ?? '') . ' ' . ($record->lastname ?? ''))
                ->action(function ($record) {
                    $this->modalUserId = $record->id;
                    $this->modalPage = 1;
                })
                ->modalContent(fn ($record) => $this->getModalContent($record))
                ->modalWidth('7xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Fermer')
                ->extraModalFooterActions(function ($record) {
                    $userId = $this->modalUserId ?? $record->id;
                    $currentPage = $this->modalPage ?? 1;
                    
                    // Calculer le nombre total de pages
                    $perPage = 25;
                    $dateFrom = null;
                    $dateTo = null;
                    try {
                        $dateFilterState = $this->getTableFilterState('date_range');
                        if (is_array($dateFilterState)) {
                            $dateFrom = $dateFilterState['from'] ?? null;
                            $dateTo = $dateFilterState['to'] ?? null;
                            if ($dateFrom && is_string($dateFrom)) {
                                $dateFrom = Carbon::parse($dateFrom);
                            }
                            if ($dateTo && is_string($dateTo)) {
                                $dateTo = Carbon::parse($dateTo);
                            }
                        }
                    } catch (\Exception $e) {}
                    
                    $productsQuery = ChemProduct::query()->where('created_by', $userId);
                    if ($dateFrom) {
                        $productsQuery->whereDate('created_at', '>=', $dateFrom);
                    }
                    if ($dateTo) {
                        $productsQuery->whereDate('created_at', '<=', $dateTo);
                    }
                    $total = $productsQuery->count();
                    $lastPage = ceil($total / $perPage);
                    
                    $actions = [];
                    
                    if ($currentPage > 1) {
                        $actions[] = Action::make('prevPage')
                            ->label('Précédent')
                            ->icon('heroicon-o-chevron-left')
                            ->color('gray')
                            ->outlined()
                            ->action(function () use ($currentPage) {
                                $this->modalPage = max(1, $currentPage - 1);
                                $this->dispatch('$refresh');
                            });
                    }
                    
                    if ($currentPage < $lastPage) {
                        $actions[] = Action::make('nextPage')
                            ->label('Suivant')
                            ->icon('heroicon-o-chevron-right')
                            ->color('gray')
                            ->outlined()
                            ->action(function () use ($currentPage) {
                                $this->modalPage = $currentPage + 1;
                                $this->dispatch('$refresh');
                            });
                    }
                    
                    // Ajouter les boutons de pages numérotées
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($lastPage, $currentPage + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        if ($i !== $currentPage) {
                            $actions[] = Action::make('gotoPage' . $i)
                                ->label((string) $i)
                                ->color('gray')
                                ->outlined()
                                ->action(function () use ($i) {
                                    $this->modalPage = $i;
                                    $this->dispatch('$refresh');
                                });
                        }
                    }
                    
                    // Ajouter l'action d'export Excel
                    $actions[] = Action::make('exportExcel')
                        ->label('Exporter Excel')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->outlined()
                        ->action(function () use ($userId, $dateFrom, $dateTo) {
                            return $this->exportToExcel($userId, $dateFrom, $dateTo);
                        });
                    
                    return $actions;
                }),
            
            // Actions d'export dans le tableau principal
            Action::make('exportExcelTable')
                ->label('Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->button()
                ->outlined()
                ->action(function ($record) {
                    $dateFrom = null;
                    $dateTo = null;
                    try {
                        $dateFilterState = $this->getTableFilterState('date_range');
                        if (is_array($dateFilterState)) {
                            $dateFrom = $dateFilterState['from'] ?? null;
                            $dateTo = $dateFilterState['to'] ?? null;
                            if ($dateFrom && is_string($dateFrom)) {
                                $dateFrom = Carbon::parse($dateFrom);
                            }
                            if ($dateTo && is_string($dateTo)) {
                                $dateTo = Carbon::parse($dateTo);
                            }
                        }
                    } catch (\Exception $e) {}
                    
                    return $this->exportToExcel($record->id, $dateFrom, $dateTo);
                }),
            
        ];
    }

    public function exportToExcel(int $userId, ?Carbon $dateFrom = null, ?Carbon $dateTo = null)
    {
        $user = User::find($userId);
        $userName = $user ? ($user->firstname . ' ' . $user->lastname) : 'Utilisateur';
        
        // Générer une clé de cache pour l'export
        $cacheKey = 'export_excel_' . $userId . '_' . ($dateFrom ? $dateFrom->format('Y-m-d') : 'all') . '_' . ($dateTo ? $dateTo->format('Y-m-d') : 'all');
        $fileName = 'produits_' . str_replace(' ', '_', $userName) . '_' . now()->format('Ymd_His') . '.xlsx';
        
        // Vérifier si le fichier est en cache
        $disk = Storage::disk('local');
        $dir = 'tmp';
        $disk->makeDirectory($dir);
        $cachedFilePath = $dir . '/' . $cacheKey . '.xlsx';
        
        // Si le fichier existe en cache et a moins de 1 heure, le retourner
        if ($disk->exists($cachedFilePath) && (now()->timestamp - $disk->lastModified($cachedFilePath)) < 3600) {
            return response()->download($disk->path($cachedFilePath), $fileName);
        }
        
        // Construire la requête
        $query = ChemProduct::query()
            ->where('created_by', $userId)
            ->with(['category', 'manufacturer', 'form'])
            ->orderByDesc('created_at');

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $path = $disk->path($cachedFilePath);

        $writer = SimpleExcelWriter::create($path)->addHeader([
            'Numéro',
            'Nom commercial',
            'DCI',
            'Marque',
            'Catégorie',
            'Fabricant',
            'Forme galénique',
            'Dosage',
            'Conditionnement',
            'Code ATC',
            'Prix réf.',
            'Statut',
            'Prescription',
            'Images',
            'Auteur manquant',
            'Date d\'encodage',
        ]);

        $rowNumber = 0;
        $query->chunk(1000, function ($products) use ($writer, &$rowNumber) {
            $writer->addRows($products->map(function (ChemProduct $product) use (&$rowNumber) {
                $rowNumber++;
                
                // Vérifier si le produit a des images
                $hasImages = false;
                try {
                    $imageKeys = $product->imageKeys();
                    $hasImages = !empty($imageKeys) && count($imageKeys) > 0;
                } catch (\Exception $e) {
                    $hasImages = false;
                }

                return [
                    'Numéro' => $rowNumber,
                    'Nom commercial' => $product->name ?? '—',
                    'DCI' => $product->generic_name ?? '—',
                    'Marque' => $product->brand_name ?? '—',
                    'Catégorie' => $product->category ? $product->category->name : '—',
                    'Fabricant' => $product->manufacturer ? $product->manufacturer->name : '—',
                    'Forme galénique' => $product->form ? $product->form->name : '—',
                    'Dosage' => $product->strength ? (rtrim(rtrim(number_format((float) $product->strength, 2, '.', ''), '0'), '.') . ' ' . ($product->unit ?? '')) : '—',
                    'Conditionnement' => $product->packaging ?? '—',
                    'Code ATC' => $product->atc_code ?? '—',
                    'Prix réf.' => $product->price_ref ? (number_format((float) $product->price_ref, 2, '.', ' ') . ' ' . ($product->currency ?? 'USD')) : '—',
                    'Statut' => ((int)($product->status ?? 0) === 1) ? 'Actif' : 'Inactif',
                    'Prescription' => ((int)($product->with_prescription ?? 0) === 1) ? 'Obligatoire' : 'Optionelle',
                    'Images' => $hasImages ? 'Oui' : 'Non',
                    'Auteur manquant' => (empty($product->created_by) || empty($product->updated_by)) ? 'Oui' : 'Non',
                    'Date d\'encodage' => $product->created_at ? $product->created_at->format('d/m/Y H:i') : '—',
                ];
            })->all());
        });

        $writer->close();

        // Ne pas supprimer le fichier après envoi pour le cache, mais le nettoyer après 1 heure
        return response()->download($path, $fileName);
    }

    public function exportMissingAuthorsToExcel()
    {
        $cacheKey = 'export_missing_authors_all';
        $fileName = 'produits_sans_auteur_' . now()->format('Ymd_His') . '.xlsx';

        $disk = Storage::disk('local');
        $dir = 'tmp';
        $disk->makeDirectory($dir);
        $cachedFilePath = $dir . '/' . $cacheKey . '.xlsx';

        if ($disk->exists($cachedFilePath) && (now()->timestamp - $disk->lastModified($cachedFilePath)) < 3600) {
            return response()->download($disk->path($cachedFilePath), $fileName);
        }

        $query = ChemProduct::query()
            ->where(function (Builder $q) {
                $q->whereNull('created_by')
                    ->orWhere('created_by', 0)
                    ->orWhereNull('updated_by')
                    ->orWhere('updated_by', 0);
            })
            ->with(['category', 'manufacturer', 'form'])
            ->orderByDesc('created_at');

        $path = $disk->path($cachedFilePath);

        $writer = SimpleExcelWriter::create($path)->addHeader([
            'Numéro',
            'Nom commercial',
            'DCI',
            'Marque',
            'Catégorie',
            'Fabricant',
            'Forme galénique',
            'Dosage',
            'Conditionnement',
            'Code ATC',
            'Prix réf.',
            'Statut',
            'Prescription',
            'Images',
            'Auteur manquant',
            'Date d\'encodage',
        ]);

        $rowNumber = 0;
        $query->chunk(1000, function ($products) use ($writer, &$rowNumber) {
            $writer->addRows($products->map(function (ChemProduct $product) use (&$rowNumber) {
                $rowNumber++;

                $hasImages = false;
                try {
                    $imageKeys = $product->imageKeys();
                    $hasImages = !empty($imageKeys) && count($imageKeys) > 0;
                } catch (\Exception $e) {
                    $hasImages = false;
                }

                return [
                    'Numéro' => $rowNumber,
                    'Nom commercial' => $product->name ?? '—',
                    'DCI' => $product->generic_name ?? '—',
                    'Marque' => $product->brand_name ?? '—',
                    'Catégorie' => $product->category ? $product->category->name : '—',
                    'Fabricant' => $product->manufacturer ? $product->manufacturer->name : '—',
                    'Forme galénique' => $product->form ? $product->form->name : '—',
                    'Dosage' => $product->strength ? (rtrim(rtrim(number_format((float) $product->strength, 2, '.', ''), '0'), '.') . ' ' . ($product->unit ?? '')) : '—',
                    'Conditionnement' => $product->packaging ?? '—',
                    'Code ATC' => $product->atc_code ?? '—',
                    'Prix réf.' => $product->price_ref ? (number_format((float) $product->price_ref, 2, '.', ' ') . ' ' . ($product->currency ?? 'USD')) : '—',
                    'Statut' => ((int)($product->status ?? 0) === 1) ? 'Actif' : 'Inactif',
                    'Prescription' => ((int)($product->with_prescription ?? 0) === 1) ? 'Obligatoire' : 'Optionelle',
                    'Images' => $hasImages ? 'Oui' : 'Non',
                    'Auteur manquant' => (empty($product->created_by) || empty($product->updated_by)) ? 'Oui' : 'Non',
                    'Date d\'encodage' => $product->created_at ? $product->created_at->format('d/m/Y H:i') : '—',
                ];
            })->all());
        });

        $writer->close();

        return response()->download($path, $fileName);
    }

}
