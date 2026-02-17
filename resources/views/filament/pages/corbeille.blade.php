<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Filtrer par type</x-slot>
            <x-slot name="description">
                Sélectionnez le type d'éléments à afficher dans la corbeille.
            </x-slot>

            <div class="flex flex-wrap gap-2">
                @foreach ($this->getTrashableModels() as $modelClass => $label)
                    <a
                        href="{{ request()->fullUrlWithQuery(['type' => $modelClass]) }}"
                        wire:navigate
                        @class([
                            'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition',
                            'bg-primary-600 text-white shadow hover:bg-primary-500' => $activeModel === $modelClass,
                            'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600' => $activeModel !== $modelClass,
                        ])
                    >
                        {{ $label }}
                        <span @class([
                            'ml-1.5 rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-white/25 text-white' => $activeModel === $modelClass,
                            'bg-gray-200 text-gray-700 dark:bg-gray-600 dark:text-gray-300' => $activeModel !== $modelClass,
                        ])>
                            {{ $this->getTrashCountForModel($modelClass) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">
                {{ $activeModel ? ($this->getTrashableModels()[$activeModel] ?? 'Éléments') : 'Sélectionnez un type' }}
            </x-slot>
            <x-slot name="description">
                Éléments avec status = 0 (corbeille). Vous pouvez les restaurer ou les supprimer définitivement (super admin uniquement).
            </x-slot>

            @if ($activeModel)
                {{ $this->table }}
            @else
                <p class="text-gray-500 dark:text-gray-400">Veuillez sélectionner un type ci-dessus.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
