<?php

namespace App\Filament\Resources\UploadTestResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\UploadTestResource;

class CreateUploadTest extends CreateRecord
{
    protected static string $resource = UploadTestResource::class;

    protected function afterCreate(): void
    {
        // Vérif de base après enregistrement (optionnel)
        $r = $this->record;
        try {
            $disk = $r->disk ?? 's3';
            if ($r->single_file && ! Storage::disk($disk)->exists($r->single_file)) {
                Notification::make()->title("Attention: le fichier unique n'existe pas sur le disque.")
                    ->warning()->send();
            }
        } catch (\Throwable $e) {
            $r->update(['error_message' => $e->getMessage()]);
            Notification::make()->title('Erreur post-enregistrement')
                ->body($e->getMessage())->danger()->send();
        }
    }
}
