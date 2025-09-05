<?php

namespace App\Filament\Resources\UploadTestResource\Pages;

use App\Filament\Resources\UploadTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUploadTests extends ListRecords
{
    protected static string $resource = UploadTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
