@php
    // Récupère un tableau d'URLs (gère string JSON ou array)
    $urls = $getRecord()->mediaUrls('images');
@endphp

@if (empty($urls))
    <span class="text-gray-400">—</span>
@else
    <div class="flex flex-wrap gap-2">
        @foreach ($urls as $url)
            <a href="{{ $url }}" target="_blank" rel="noopener">
                <img src="{{ $url }}" class="h-12 w-12 rounded object-cover" loading="lazy" alt="">
            </a>
        @endforeach
    </div>
@endif
