<?php

namespace App\Filament\Resources\MainStatusResource\Pages;

use App\Filament\Resources\MainStatusResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMainStatuses extends ListRecords
{
    protected static string $resource = MainStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un statut')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
