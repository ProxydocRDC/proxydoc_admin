<x-filament-panels::page>
    <div>
        @if (! $completed)
            <div wire:poll.200ms="processChunk"></div>
        @endif

        <x-filament::section>
            <x-slot name="heading">
                Mise à jour des quantités en masse
            </x-slot>
            <x-slot name="description">
                @if ($cancelled)
                    Opération annulée.
                @elseif ($completed)
                    Mise à jour terminée.
                @else
                    Quantité « {{ $quantity }} » en cours d'application...
                @endif
            </x-slot>

            <div class="space-y-6">
                {{-- Loader --}}
                @if (! $completed)
                    <div class="flex items-center justify-center gap-3 rounded-lg border border-primary-200 bg-primary-50/50 py-4 dark:border-primary-800 dark:bg-primary-950/20">
                        <x-filament::icon
                            icon="heroicon-o-arrow-path"
                            class="h-8 w-8 animate-spin text-primary-600 dark:text-primary-400"
                        />
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Traitement en cours...</span>
                    </div>
                @endif

                {{-- Quantité affichée --}}
                <div class="rounded-lg bg-gray-50 dark:bg-gray-800/50 p-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Quantité appliquée</span>
                        <span class="text-lg font-bold text-primary-600 dark:text-primary-400">{{ $quantity }}</span>
                    </div>
                </div>

                {{-- Barre de progression --}}
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

                {{-- Bouton Annuler (visible pendant le traitement) --}}
                @if (! $completed)
                    <div class="flex justify-center pt-4">
                        <x-filament::button wire:click="cancel" color="danger" variant="outline">
                            Annuler l'opération
                        </x-filament::button>
                    </div>
                @endif

                {{-- Bouton Retour (visible après) --}}
                @if ($completed || $cancelled)
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
