<x-filament-panels::page>
    <div>
        @if (! $completed)
            <div wire:poll.1.5s="processChunk"></div>
        @endif

        {{-- Pharmacie concernée - en évidence en haut --}}
        <div class="mb-6 rounded-xl border-2 border-primary-200 bg-primary-50 p-4 dark:border-primary-800 dark:bg-primary-950/30">
            <div class="flex items-center gap-3">
                <x-filament::icon icon="heroicon-o-building-storefront" class="h-8 w-8 text-primary-600 dark:text-primary-400" />
                <div>
                    <div class="text-xs font-medium uppercase tracking-wide text-primary-600 dark:text-primary-400">Pharmacie concernée</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $pharmacyName ?? '—' }}</div>
                </div>
            </div>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Affectation en cours
            </x-slot>
            <x-slot name="description">
                @if ($completed)
                    Affectation terminée pour {{ $pharmacyName }}.
                @else
                    Traitement en cours pour {{ $pharmacyName }}...
                @endif
            </x-slot>

            <div class="space-y-6">
                {{-- Loader : visible pendant le traitement --}}
                @if (! $completed)
                    <div class="flex items-center justify-center gap-3 rounded-lg border border-primary-200 bg-primary-50/50 py-4 dark:border-primary-800 dark:bg-primary-950/20">
                        <x-filament::icon
                            icon="heroicon-o-arrow-path"
                            class="h-8 w-8 animate-spin text-primary-600 dark:text-primary-400"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Traitement en cours...</span>
                    </div>
                @endif

                {{-- Barre de progression - affichée en premier --}}
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Progression</span>
                        <span class="font-bold">{{ $processed }} / {{ $total }}</span>
                    </div>
                    <div class="h-5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                        <div
                            class="h-full bg-primary-600 transition-all duration-150 ease-out dark:bg-primary-500"
                            style="width: {{ $total > 0 ? max(2, $this->getProgressPercent()) : 100 }}%"
                        ></div>
                    </div>
                </div>

                {{-- Comptage détaillé --}}
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $processed }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Traités</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center">
                        <div class="text-2xl font-bold text-success-600 dark:text-success-400">{{ $created }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Créés</div>
                    </div>
                    <div class="rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-center sm:col-span-2 sm:col-span-1">
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ $skipped }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Ignorés (déjà présents)</div>
                    </div>
                </div>

                @if ($completed)
                    <div class="flex justify-end pt-4">
                        <x-filament::button wire:click="backToList" color="primary">
                            Retour à la liste
                        </x-filament::button>
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
