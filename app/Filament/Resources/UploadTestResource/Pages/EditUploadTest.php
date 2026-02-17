<?php

namespace App\Filament\Resources\UploadTestResource\Pages;

use App\Filament\Resources\UploadTestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUploadTest extends EditRecord
{
    protected static string $resource = UploadTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\TrashAction::makeForPage(),
        ];
    }
}
