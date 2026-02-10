@php
    $modalId = $missingAuthorsModalId ?? 'missing-authors-modal';
    $modalData = $this->getMissingAuthorsModalData();
    $products = $modalData['products'];
    $total = $modalData['total'];
    $perPage = $modalData['perPage'];
    $currentPage = $modalData['currentPage'];
    $lastPage = max(1, (int) ceil($total / max(1, $perPage)));
@endphp

<div class="mt-3">
    <div class="overflow-x-auto border-t border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <tbody class="bg-white dark:bg-gray-900">
                <tr class="bg-gray-50 dark:bg-gray-800">
                    <td colspan="3" class="px-4 py-3 text-sm font-medium text-gray-700 dark:text-gray-200">
                        Auteur manquant
                        <div class="mt-2 flex flex-wrap gap-2">
                            <x-filament::button size="sm" color="info" wire:click="openMissingAuthorsModal">
                                Voir les produits
                            </x-filament::button>
                            <x-filament::button size="sm" color="success" wire:click="exportMissingAuthorsToExcel">
                                Exporter Excel
                            </x-filament::button>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ $missingAuthorsWeek }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ $missingAuthorsMonth }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ $missingAuthorsPeriod }}</td>
                    <td class="px-4 py-3 text-sm text-center text-gray-700 dark:text-gray-300">{{ $missingAuthorsTotal }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <x-filament::modal id="{{ $modalId }}" width="7xl">
        <x-slot name="heading">Produits sans auteur</x-slot>
        <x-slot name="description">Produits dont l'auteur (création ou modification) est manquant.</x-slot>

        @include('filament.widgets.products-modal', $modalData)

        @if($lastPage > 1)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    Page {{ $currentPage }} sur {{ $lastPage }}
                </div>
                <div class="flex gap-2">
                    @if($currentPage > 1)
                        <x-filament::button size="sm" color="gray" wire:click="changeMissingAuthorsPage({{ $currentPage - 1 }})">
                            Précédent
                        </x-filament::button>
                    @endif
                    @if($currentPage < $lastPage)
                        <x-filament::button size="sm" color="gray" wire:click="changeMissingAuthorsPage({{ $currentPage + 1 }})">
                            Suivant
                        </x-filament::button>
                    @endif
                </div>
            </div>
        @endif
    </x-filament::modal>
</div>
