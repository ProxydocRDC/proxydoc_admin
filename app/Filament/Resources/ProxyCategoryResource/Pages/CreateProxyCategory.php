<?php

namespace App\Filament\Resources\ProxyCategoryResource\Pages;

use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\ProxyCategoryResource;

class CreateProxyCategory extends CreateRecord
{
    protected static string $resource = ProxyCategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();
        $data['status']     = $data['status'] ?? 1;
        return $data;
    }
}
