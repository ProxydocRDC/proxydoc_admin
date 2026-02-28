<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = parent::mutateFormDataBeforeFill($data);

        if (! empty($data['phone'])) {
            $phone = preg_replace('/\s+/', '', $data['phone']);
            if (preg_match('/^\+?(\d{2,3})(\d+)$/', $phone, $m)) {
                $data['phone_country_code'] = $m[1];
                $data['phone']              = $m[2];
            }
        }

        return $data;
    }
}
