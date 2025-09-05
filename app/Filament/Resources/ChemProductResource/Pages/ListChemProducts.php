<?php

namespace App\Filament\Resources\ChemProductResource\Pages;

use Filament\Actions;
use App\Models\ChemProduct;
use App\Models\ChemCategory;
use App\Models\ChemManufacturer;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Pages\ListRecords;
use Spatie\SimpleExcel\SimpleExcelReader;
use App\Filament\Resources\ChemProductResource;

class ListChemProducts extends ListRecords
{
    protected static string $resource = ChemProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Ajouter un Produit')
                ->icon('heroicon-o-plus-circle'),



            Actions\Action::make('import')
                ->label('Importer (Excel/CSV)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier Excel ou CSV')
                        ->acceptedFileTypes([
                            'text/csv', 'text/plain', '.csv',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
                            '.xlsx',
                        ])
                        ->storeFiles(false) // on lit le tmp directement
                        ->required(),
                ])
                ->action(function (array $data) {
                    $path = $data['file']->getRealPath();

                    $rows = SimpleExcelReader::create($path)->getRows(); // stream chunké

                    $supplierId = Auth::user()?->supplier?->id;

                    $count = 0;
                    $rows->each(function (array $row) use (&$count, $supplierId) {
                        // En-têtes attendues : code,label,description,status,category_code,manufacturer_code,price,currency
                        $categoryId = !empty($row['category_code'] ?? null)
                            ? ChemCategory::query()->where('code', $row['category_code'])->value('id')
                            : null;

                        $manufacturerId = !empty($row['manufacturer_code'] ?? null)
                            ? ChemManufacturer::query()->where('code', $row['manufacturer_code'])->value('id')
                            : null;

                        // Upsert par "code"
                        ChemProduct::updateOrCreate(
                            ['code' => (string) ($row['code'] ?? '')],
                            [
                                'label'           => $row['label'] ?? null,
                                'description'     => $row['description'] ?? null,
                                'status'          => isset($row['status']) ? (int) $row['status'] : 1,
                                'supplier_id'     => $supplierId,
                                'category_id'     => $categoryId,
                                'manufacturer_id' => $manufacturerId,
                                'price'           => $row['price'] ?? null,
                                'currency'        => $row['currency'] ?? null,
                                'updated_by'      => Auth::id(),
                                'created_by'      => Auth::id(),
                            ]
                        );

                        $count++;
                    });

                    Notification::make()
                        ->title('Import terminé')
                        ->body("{$count} lignes traitées.")
                        ->success()
                        ->send();
                })
                ->modalHeading('Importer des produits')
                ->extraModalFooterActions([
                    Actions\Action::make('template')
                        ->label('Télécharger le modèle CSV')
                        ->icon('heroicon-o-document-arrow-down')
                        ->url(route('chem-products.template'))
                        ->openUrlInNewTab(),
                ]),
        ];
    }
}
