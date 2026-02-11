<?php

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Redirect()->to(
        Filament::getPanel('admin')->getLoginUrl()
    );
});
Route::get('/templates/chem-products.csv', function () {
    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="chem-products-template.csv"',
    ];
    $rows = [
        ['code', 'label', 'description', 'status', 'category_code', 'manufacturer_code', 'price', 'currency'],
        ['AMOX500', 'Amoxicilline 500mg', 'Boîte de 12', 1, 'ANTIB', 'PFIZER', 12.5, 'USD'],
    ];
    $out = fopen('php://temp', 'r+');
    foreach ($rows as $r) {
        fputcsv($out, $r);
    }

    rewind($out);
    return response(stream_get_contents($out), 200, $headers);
})->name('chem-products.template');
// routes/web.php
// Route::get('/exports/templates/products.csv', function () {
//     $headers = [
//         'name','generic_name','brand_name','price_ref',
//         'category_code','manufacturer_name','form_name',
//         'sku','barcode','strength','dosage','unit',
//         'stock','min_stock','price_sale','price_purchase',
//         'description','is_active',
//     ];

//     $content = implode(',', $headers)."\n";
//     return response($content, 200, [
//         'Content-Type' => 'text/csv',
//         'Content-Disposition' => 'attachment; filename="template_products.csv"',
//     ]);
// })->name('products.template');
// routes/web.php
Route::middleware('auth')->get('/imports/reports/{file}', function (string $file) {
    $path = storage_path("app/imports/reports/{$file}");
    abort_unless(is_file($path), 404);
    return response()->download($path, $file);
})->name('imports.report');

Route::get('/js/filament/{path}', function (string $path) {
    $fullPath = public_path('js/filament/' . $path);
    abort_unless(is_file($fullPath), 404);
    return response()->file($fullPath, ['Content-Type' => 'application/javascript; charset=UTF-8']);
})->where('path', '.*');

Route::get('/css/filament/{path}', function (string $path) {
    $fullPath = public_path('css/filament/' . $path);
    abort_unless(is_file($fullPath), 404);
    return response()->file($fullPath, ['Content-Type' => 'text/css; charset=UTF-8']);
})->where('path', '.*');

Route::fallback(function () {
    abort(404);
});
// routes/web.php
Route::middleware(['auth'])->get('/exports/templates/products.csv', function () {
    // Entêtes alignées sur chem_products (+ colonnes FK "humaines")
    $headers = [
        'name', 'generic_name', 'brand_name', 'price_ref',
        'category_code', 'manufacturer_name', 'form_name',
        'strength', 'unit', 'packaging', 'atc_code',
        'indications', 'contraindications', 'side_effects', 'storage_conditions',
        'shelf_life_months', 'images', 'description', 'composition', 'with_prescription',
    ];

    // (facultatif) une ligne d’exemple correctement échappée
    $example = [
        'Doliprane 500 mg', 'Paracetamol', 'Sanofi', '1.20',
        'ANALG-001', 'Sanofi', 'Comprimé',
        '500', 'mg', 'Boîte de 16 cp', 'N02BE01',
        'Douleurs, fièvre', '', '', '15–25 °C',
        '24',
        // images: clés S3 OU URLs séparées par "|"
        'products/doliprane.jpg|products/doliprane-2.jpg',
        'Antalgique', 'Paracetamol 500 mg', '1',
    ];

    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, $headers);
    fputcsv($stream, $example);
    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    return response($csv, 200, [
        'Content-Type'        => 'text/csv; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="template_products.csv"',
    ]);
})->name('products.template');
Route::middleware(['auth'])->get('/templates/pharmacies.csv', function () {
    $headers = [
        // requis
        'name', 'zone_id',
        // liaison user/supplier (au moins un des champs par entité)
        'supplier_id', 'supplier_name',
        'user_id', 'user_email', 'user_name',
        // optionnels
        'status', 'phone', 'email', 'address', 'description', 'logo', 'rating', 'nb_review',
    ];
    $example = [
        'Pharma Belle Vue', '3',
        '', 'Fournisseur A',
        '', 'manager@exemple.com', '',
        '1', '+243900000', 'pharma@example.com', 'Av. X, Kin', 'Ouvert 7j/7',
        'pharmacies/01K51CH3...png', '4.5', '23',
    ];

    $content = implode(',', $headers) . "\n" . implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example)) . "\n";

    return response($content, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="template_pharmacies.csv"',
    ]);
})->name('pharmacies.template.csv');
Route::middleware('auth')->get('/imports/reports/{file}', function (string $file) {
    $path = storage_path("app/imports/reports/{$file}");
    abort_unless(is_file($path), 404);
    return response()->download($path, $file);
})->name('imports.report');
Route::middleware('auth')->get('/templates/pharmacy_products.csv', function () {
    $headers = [
        // FK (au moins un champ par entité)
        'pharmacy_id', 'pharmacy_name',
        'product_id', 'product_sku', 'product_name',
        'manufacturer_id', 'manufacturer_name',
        // données
        'status', 'lot_ref', 'origin_country', 'expiry_date',
        'cost_price', 'sale_price', 'currency', 'stock_qty', 'reorder_level',
        'image', 'description',
    ];

    $example = [
        '', 'Pharmacie Belle Vue',
        '', 'SKU-001', 'Doliprane 500',
        '', 'Sanofi',
        '1', 'DL2301', 'FRA', '2026-12-31',
        '0.85', '1.20', 'USD', '250', '20',
        'pharmacies/01K51CH3...png', 'Antalgique.',
    ];

    $content = implode(',', $headers) . "\n" . implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $example)) . "\n";

    return response($content, 200, [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="template_pharmacy_products.csv"',
    ]);
})->name('pharmacy_products.template.csv');

// Téléchargement des rapports d'échec (si pas déjà fait)
Route::middleware('auth')->get('/imports/reports/{file}', function (string $file) {
    $path = storage_path("app/imports/reports/{$file}");
    abort_unless(is_file($path), 404);
    return response()->download($path, $file);
})->name('imports.report');
