@php
    use Illuminate\Support\Facades\Storage;

    $files = $getState();
    if (is_string($files)) {
        $decoded = json_decode($files, true);
        if (json_last_error() === JSON_ERROR_NONE) $files = $decoded;
    }

    $record   = $getRecord();
    $disk     = $record->disk ?? 's3';
    $isPublic = (bool) ($record->is_public ?? true);
@endphp

@if(empty($files) || !is_array($files))
    <span class="text-gray-400">—</span>
@else
    <ul class="space-y-1">
        @foreach($files as $path)
            @php
                $url  = $isPublic
                    ? Storage::disk($disk)->url($path)
                    : Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(5));
                $name = basename($path);
            @endphp
            <li>
                <a href="{{ $url }}" target="_blank" class="text-primary-600 hover:underline">
                    {{ $name }}
                </a>
            </li>
        @endforeach
    </ul>
@endif
