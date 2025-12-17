@php
    $records = $this->getTableRecords();
@endphp

<x-filament-panels::page>
    <div class="fi-page-content space-y-6">
        {{-- Loader de chargement --}}
        <div wire:loading class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
                <div class="flex flex-col items-center">
                    <svg class="animate-spin h-8 w-8 text-primary-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-gray-700 dark:text-gray-300 font-medium">Chargement...</p>
                </div>
            </div>
        </div>

        {{-- Barre de recherche et filtres --}}
        <div class="space-y-4">
            {{-- Barre de recherche --}}
            <div>
                <input 
                    type="search" 
                    wire:model.live.debounce.300ms="tableSearch" 
                    placeholder="Rechercher un produit..." 
                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-4 py-2"
                />
            </div>

            {{-- Filtres --}}
            <div class="flex flex-wrap gap-2">
                <select wire:model.live="tableFilters.status" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm">
                    <option value="">Tous les statuts</option>
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>

                <select wire:model.live="tableFilters.with_prescription" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm">
                    <option value="">Toutes les ordonnances</option>
                    <option value="1">Obligatoire</option>
                    <option value="0">Optionelle</option>
                </select>

                <select wire:model.live="tableFilters.category_id" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm">
                    <option value="">Toutes les catégories</option>
                    @foreach(\App\Models\ChemCategory::orderBy('name')->get() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="tableFilters.has_image" class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 py-2 text-sm">
                    <option value="">Tous les produits</option>
                    <option value="true">Avec images</option>
                    <option value="false">Sans images</option>
                </select>
            </div>

            {{-- Boutons d'action du header --}}
            <div class="flex flex-wrap gap-2">
                <a href="{{ \App\Filament\Resources\ChemProductResource::getUrl('create') }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Ajouter un produit
                </a>

                <button 
                    x-data
                    x-on:click="$wire.mountAction('importProducts')"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    Importer
                </button>

                <div class="relative">
                    <button onclick="toggleExportMenu()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Exporter
                    </button>
                    <div id="export-menu" class="hidden absolute mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg z-10">
                        <button wire:click="exportCsv" class="block w-full text-left px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">CSV</button>
                        <button wire:click="exportXlsx" class="block w-full text-left px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700">XLSX</button>
                    </div>
                </div>

                <button 
                    x-data
                    x-on:click="$wire.mountAction('downloadTemplate')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Télécharger le modèle
                </button>
            </div>
        </div>

        {{-- Grille de produits --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @forelse($records as $record)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden border border-gray-200 dark:border-gray-700 flex flex-col">
                    {{-- Slider d'images --}}
                    <div class="relative h-48 bg-gray-200 dark:bg-gray-700">
                        @php
                            $imageUrls = $record->signedImageUrls(60);
                            $imageCount = count($imageUrls);
                        @endphp
                        @if($imageCount > 0)
                            @if($imageCount > 2)
                                {{-- Slider Swiper --}}
                                <div class="swiper product-images-swiper-{{ $record->id }}" style="height: 100%;">
                                    <div class="swiper-wrapper" x-data="{ productId: {{ $record->id }}, productName: @js($record->name) }">
                                        @foreach($imageUrls as $index => $url)
                                            <div class="swiper-slide">
                                                <img 
                                                    src="{{ $url }}" 
                                                    alt="{{ $record->name }} - Image {{ $index + 1 }}"
                                                    class="w-full h-full object-cover cursor-pointer"
                                                    x-on:click="$wire.viewImagesGrid(productId, productName)"
                                                    loading="lazy"
                                                >
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="swiper-pagination swiper-pagination-{{ $record->id }}"></div>
                                    <div class="swiper-button-next swiper-button-next-{{ $record->id }}"></div>
                                    <div class="swiper-button-prev swiper-button-prev-{{ $record->id }}"></div>
                                </div>
                            @else
                                {{-- Images simples si <= 2 --}}
                                <div x-data="{ productId: {{ $record->id }}, productName: @js($record->name) }">
                                    @foreach($imageUrls as $index => $url)
                                        <img 
                                            src="{{ $url }}" 
                                            alt="{{ $record->name }} - Image {{ $index + 1 }}"
                                            class="w-full h-full object-cover cursor-pointer {{ $index > 0 ? 'hidden' : '' }}"
                                            x-on:click="$wire.viewImagesGrid(productId, productName)"
                                            loading="lazy"
                                        >
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <div class="flex items-center justify-center h-full">
                                <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        @endif
                        {{-- Badge statut --}}
                        <div class="absolute top-2 right-2 z-10">
                            @if($record->status == 1)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Actif
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    Inactif
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Contenu de la carte --}}
                    <div class="p-4 flex flex-col flex-grow">
                        <div class="flex-grow">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2 min-h-[3rem]">
                                {{ $record->name }}
                            </h3>
                            
                            @if($record->generic_name)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2 line-clamp-1">
                                    <span class="font-medium">DCI:</span> {{ $record->generic_name }}
                                </p>
                            @endif

                            {{-- Catégorie --}}
                            @if($record->category)
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-200">
                                        {{ $record->category->name }}
                                    </span>
                                </div>
                            @endif

                            {{-- Informations supplémentaires --}}
                            <div class="space-y-1 mb-3 text-xs text-gray-500 dark:text-gray-400">
                                @if($record->manufacturer)
                                    <p class="line-clamp-1">
                                        <span class="font-medium">Fabricant:</span> {{ $record->manufacturer->name }}
                                    </p>
                                @endif
                                @if($record->form)
                                    <p class="line-clamp-1">
                                        <span class="font-medium">Forme:</span> {{ $record->form->name }}
                                    </p>
                                @endif
                                @if($record->strength)
                                    <p class="line-clamp-1">
                                        <span class="font-medium">Dosage:</span> {{ $record->strength }} {{ $record->unit ?? '' }}
                                    </p>
                                @endif
                                @if($record->packaging)
                                    <p class="line-clamp-1">
                                        <span class="font-medium">Conditionnement:</span> {{ $record->packaging }}
                                    </p>
                                @endif
                            </div>

                            {{-- Prix --}}
                            @if($record->price_ref)
                                <p class="text-lg font-bold text-gray-900 dark:text-white mb-3">
                                    {{ number_format((float) $record->price_ref, 2, '.', ' ') }} {{ $record->currency ?? 'USD' }}
                                </p>
                            @endif

                            {{-- Badge prescription --}}
                            @if($record->with_prescription)
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                        Ordonnance requise
                                    </span>
                                </div>
                            @endif
                        </div>

                        {{-- Actions groupées --}}
                        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex flex-wrap gap-1 justify-center">
                                {{-- Modifier (Primary - Bleu foncé) --}}
                                <a 
                                    href="{{ \App\Filament\Resources\ChemProductResource::getUrl('edit', ['record' => $record]) }}"
                                    style="background-color: #3b82f6;"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    title="Modifier"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>

                                {{-- Modifier statut (Vert pour activer, Orange pour désactiver) --}}
                                <button
                                    type="button"
                                    onclick="confirmUpdateStatus({{ $record->id }}, {{ $record->status == 1 ? 0 : 1 }}, @js($record->name))"
                                    style="background-color: {{ $record->status == 1 ? '#f97316' : '#22c55e' }};"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    title="{{ $record->status == 1 ? 'Désactiver' : 'Activer' }}"
                                >
                                    @if($record->status == 1)
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                </button>

                                {{-- Voir les images (Bleu ciel) - Méthode Livewire personnalisée --}}
                                @if(!empty($record->images))
                                    <button
                                        type="button"
                                        x-data="{ productId: {{ $record->id }}, productName: @js($record->name) }"
                                        x-on:click="$wire.viewImagesGrid(productId, productName)"
                                        style="background-color: #0ea5e9;"
                                        class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
                                        title="Voir les images"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                @endif

                                {{-- Vider images (Jaune/Ambre) - Méthode Livewire personnalisée --}}
                                @if(!empty($record->images))
                                    <button
                                        type="button"
                                        onclick="confirmClearImages({{ $record->id }}, @js($record->name))"
                                        style="background-color: #f59e0b;"
                                        class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                        title="Vider les images"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                @endif

                                {{-- Activer/Désactiver ordonnance (Cyan/Teal) --}}
                                <button
                                    type="button"
                                    onclick="confirmTogglePrescription({{ $record->id }}, @js($record->name))"
                                    style="background-color: #14b8a6;"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                                    title="{{ $record->with_prescription ? 'Désactiver ordonnance' : 'Activer ordonnance' }}"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </button>

                                {{-- Voir détails (Violet/Indigo) - Méthode Livewire personnalisée --}}
                                <button
                                    type="button"
                                    x-data="{ productId: {{ $record->id }}, productName: @js($record->name) }"
                                    x-on:click="$wire.viewDetailsGrid(productId, productName)"
                                    style="background-color: #6366f1;"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    title="Voir tous les détails"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </button>

                                {{-- Supprimer (Rouge/Danger) --}}
                                <button
                                    type="button"
                                    onclick="confirmDelete({{ $record->id }})"
                                    style="background-color: #ef4444;"
                                    class="inline-flex items-center justify-center rounded-lg border border-transparent px-2 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                    title="Supprimer"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">Aucun produit trouvé.</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if(method_exists($records, 'links'))
            <div class="mt-6">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>

{{-- Swiper CSS --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

{{-- Swiper JS --}}
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
// Initialiser les sliders Swiper pour chaque produit
function initSwipers() {
    @foreach($records as $record)
        @if(count($record->signedImageUrls(60)) > 2)
            const swiperEl{{ $record->id }} = document.querySelector('.product-images-swiper-{{ $record->id }}');
            if (swiperEl{{ $record->id }} && !swiperEl{{ $record->id }}.swiper) {
                new Swiper('.product-images-swiper-{{ $record->id }}', {
                    loop: true,
                    pagination: {
                        el: '.swiper-pagination-{{ $record->id }}',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.swiper-button-next-{{ $record->id }}',
                        prevEl: '.swiper-button-prev-{{ $record->id }}',
                    },
                    autoplay: {
                        delay: 3000,
                        disableOnInteraction: false,
                    },
                });
            }
        @endif
    @endforeach
}

document.addEventListener('DOMContentLoaded', function() {
    initSwipers();
});

// Réinitialiser les sliders après les mises à jour Livewire
document.addEventListener('livewire:init', () => {
    Livewire.hook('morph.updated', () => {
        setTimeout(() => {
            initSwipers();
        }, 100);
    });
});

// Fonctions de confirmation pour éviter les problèmes d'échappement dans Alpine.js
window.confirmUpdateStatus = function(productId, newStatus, productName) {
    const action = newStatus == 1 ? 'activer' : 'désactiver';
    const message = 'Êtes-vous sûr de vouloir ' + action + ' le produit ' + productName + ' ?';
    if (confirm(message)) {
        const component = document.querySelector('[wire\\:id]');
        if (component && window.Livewire) {
            const wireId = component.getAttribute('wire:id');
            if (wireId) {
                window.Livewire.find(wireId).call('updateStatus', productId, newStatus);
            }
        }
    }
}

window.confirmClearImages = function(productId, productName) {
    const message = 'Voulez-vous vider les images du produit ' + productName + ' ?';
    if (confirm(message)) {
        const component = document.querySelector('[wire\\:id]');
        if (component && window.Livewire) {
            const wireId = component.getAttribute('wire:id');
            if (wireId) {
                window.Livewire.find(wireId).call('clearImagesGrid', productId);
            }
        }
    }
}

window.confirmTogglePrescription = function(productId, productName) {
    const message = 'Voulez-vous changer le statut d' + String.fromCharCode(39) + 'ordonnance pour le produit ' + productName + ' ?';
    if (confirm(message)) {
        const component = document.querySelector('[wire\\:id]');
        if (component && window.Livewire) {
            const wireId = component.getAttribute('wire:id');
            if (wireId) {
                window.Livewire.find(wireId).call('togglePrescription', productId);
            }
        }
    }
}

window.confirmDelete = function(productId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        const component = document.querySelector('[wire\\:id]');
        if (component && window.Livewire) {
            const wireId = component.getAttribute('wire:id');
            if (wireId) {
                window.Livewire.find(wireId).call('delete', productId);
            }
        }
    }
}

function toggleExportMenu() {
    const menu = document.getElementById('export-menu');
    menu.classList.toggle('hidden');
}

// Fermer le menu export en cliquant ailleurs
document.addEventListener('click', function(event) {
    const menu = document.getElementById('export-menu');
    if (menu && !menu.contains(event.target) && !event.target.closest('button')) {
        menu.classList.add('hidden');
    }
});

</script>

<style>
.swiper {
    width: 100%;
    height: 100%;
}

.swiper-slide {
    text-align: center;
    font-size: 18px;
    background: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
}

.swiper-slide img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.swiper-pagination-bullet {
    background: white;
    opacity: 0.5;
}

.swiper-pagination-bullet-active {
    opacity: 1;
}

.swiper-button-next,
.swiper-button-prev {
    color: white;
    background: rgba(0, 0, 0, 0.5);
    width: 30px;
    height: 30px;
    border-radius: 50%;
}

.swiper-button-next:after,
.swiper-button-prev:after {
    font-size: 16px;
}
</style>
