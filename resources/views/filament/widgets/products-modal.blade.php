@php
    // Les produits sont déjà paginés côté base de données
    $perPage = $perPage ?? 25;
    $currentPage = $currentPage ?? 1;
    $total = $total ?? $products->count();
    $lastPage = ceil($total / $perPage);
    $offset = ($currentPage - 1) * $perPage;
@endphp

<div class="space-y-4" wire:key="products-modal-{{ $widgetId }}-{{ $userId }}-page-{{ $currentPage }}">
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        Total : <strong>{{ $total }}</strong> produit(s)
        @if($dateFrom || $dateTo)
            dans la période sélectionnée
        @endif
    </div>
    
    <div class="products-table-container" style="max-height: 60vh; overflow-y: auto; overflow-x: auto; width: 100%;">
        <table class="divide-y divide-gray-200 dark:divide-gray-700 products-table" style="min-width: 100%; width: max-content;">
            <thead class="bg-gray-50 dark:bg-gray-800 sticky-header">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider sticky-col whitespace-nowrap">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Images</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Nom commercial</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">DCI</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Marque</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Catégorie</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Fabricant</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Forme galénique</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Dosage</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Conditionnement</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Code ATC</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Prix réf.</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Statut</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Prescription</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider whitespace-nowrap">Date d'encodage</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($products as $index => $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 sticky-col whitespace-nowrap">{{ $offset + $index + 1 }}</td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            @php
                                try {
                                    $imageUrls = $product->signedImageUrls(10) ?? [];
                                    $hasImages = !empty($imageUrls) && is_array($imageUrls) && count($imageUrls) > 0;
                                } catch (\Exception $e) {
                                    $hasImages = false;
                                }
                            @endphp
                            @if($hasImages)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Oui</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">Non</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap">{{ $product->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->generic_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->brand_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->category ? $product->category->name : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->manufacturer ? $product->manufacturer->name : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->form ? $product->form->name : '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            @if($product->strength)
                                {{ rtrim(rtrim(number_format((float) $product->strength, 2, '.', ''), '0'), '.') }} {{ $product->unit ?? '' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->packaging ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->atc_code ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">
                            @if($product->price_ref)
                                {{ number_format((float) $product->price_ref, 2, '.', ' ') }} {{ $product->currency ?? 'USD' }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            @if((int)($product->status ?? 0) === 1)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Actif</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Inactif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                            @if((int)($product->with_prescription ?? 0) === 1)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">Obligatoire</span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Optionelle</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Aucun produit trouvé
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($lastPage > 1)
        <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
            <div class="flex flex-col items-center justify-center space-y-2">
                <div class="text-sm text-gray-700 dark:text-gray-300">
                    Affichage de <span class="font-medium">{{ $offset + 1 }}</span> à <span class="font-medium">{{ min($offset + $perPage, $total) }}</span> sur <span class="font-medium">{{ $total }}</span> résultats
                    <span class="mx-2">•</span>
                    Page <span class="font-medium">{{ $currentPage }}</span> sur <span class="font-medium">{{ $lastPage }}</span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Utilisez les boutons dans le pied de page du modal pour naviguer entre les pages.
                </p>
            </div>
        </div>
    @endif
</div>

<style>
.products-table-container {
    position: relative;
}

.products-table thead.sticky-header {
    position: sticky;
    top: 0;
    z-index: 10;
}

.products-table thead.sticky-header th {
    background-color: rgb(249, 250, 251);
    border-bottom: 2px solid rgb(229, 231, 235);
}

.dark .products-table thead.sticky-header th {
    background-color: rgb(31, 41, 55);
    border-bottom: 2px solid rgb(55, 65, 81);
}

.products-table thead.sticky-header th.sticky-col {
    background-color: rgb(249, 250, 251);
    position: sticky;
    left: 0;
    z-index: 11;
}

.dark .products-table thead.sticky-header th.sticky-col {
    background-color: rgb(31, 41, 55);
}

.products-table tbody td.sticky-col {
    background-color: rgb(255, 255, 255);
    position: sticky;
    left: 0;
    z-index: 1;
}

.dark .products-table tbody td.sticky-col {
    background-color: rgb(17, 24, 39);
}

.products-table tbody tr:hover td.sticky-col {
    background-color: rgb(249, 250, 251);
}

.dark .products-table tbody tr:hover td.sticky-col {
    background-color: rgb(31, 41, 55);
}

.products-table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.products-table-container::-webkit-scrollbar-track {
    background: rgb(243, 244, 246);
    border-radius: 4px;
}

.dark .products-table-container::-webkit-scrollbar-track {
    background: rgb(31, 41, 55);
}

.products-table-container::-webkit-scrollbar-thumb {
    background: rgb(156, 163, 175);
    border-radius: 4px;
}

.products-table-container::-webkit-scrollbar-thumb:hover {
    background: rgb(107, 114, 128);
}
</style>
