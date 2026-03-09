<?php

namespace App\Filament\Resources\ProxyPatientResource\Pages;

use App\Filament\Resources\ProxyPatientResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateProxyPatient extends CreateRecord
{
    protected static string $resource = ProxyPatientResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = parent::mutateFormDataBeforeCreate($data);

        if (($data['relation'] ?? null) === 'self') {
            $data['user_id'] = $data['user_id'] ?? Auth::id();
        }

        // created_by = user parent (user_id), obligatoire pour la colonne NOT NULL
        $data['created_by'] = $data['user_id'] ?? Auth::id();
        $data['updated_by'] = $data['updated_by'] ?? Auth::id() ?? $data['created_by'];
        $data['status']     = $data['status'] ?? 1;

        return $data;
    }
}
