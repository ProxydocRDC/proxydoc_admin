<?php

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return Redirect()->route('admin');
});
Route::get('/templates/chem-products.csv', function () {
    $headers = [
        'Content-Type'        => 'text/csv',
        'Content-Disposition' => 'attachment; filename="chem-products-template.csv"',
    ];
    $rows = [
        ['code','label','description','status','category_code','manufacturer_code','price','currency'],
        ['AMOX500','Amoxicilline 500mg','Boîte de 12',1,'ANTIB','PFIZER',12.5,'USD'],
    ];
    $out = fopen('php://temp','r+');
    foreach ($rows as $r) fputcsv($out, $r);
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
// routes/web.php
Route::middleware(['auth'])->get('/exports/templates/products.csv', function () {
    // Entêtes alignées sur chem_products (+ colonnes FK "humaines")
    $headers = [
        'name','generic_name','brand_name','price_ref',
        'category_code','manufacturer_name','form_name',
        'strength','unit','packaging','atc_code',
        'indications','contraindications','side_effects','storage_conditions',
        'shelf_life_months','images','description','composition','with_prescription',
    ];

    // (facultatif) une ligne d’exemple correctement échappée
    $example = [
        'Doliprane 500 mg','Paracetamol','Sanofi','1.20',
        'ANALG-001','Sanofi','Comprimé',
        '500','mg','Boîte de 16 cp','N02BE01',
        'Douleurs, fièvre','','','15–25 °C',
        '24',
        // images: clés S3 OU URLs séparées par "|"
        'products/doliprane.jpg|products/doliprane-2.jpg',
        'Antalgique','Paracetamol 500 mg','1',
    ];

    $stream = fopen('php://temp', 'r+');
    fputcsv($stream, $headers);
    fputcsv($stream, $example);
    rewind($stream);
    $csv = stream_get_contents($stream);
    fclose($stream);

    return response($csv, 200, [
        'Content-Type' => 'text/csv; charset=utf-8',
        'Content-Disposition' => 'attachment; filename="template_products.csv"',
    ]);
})->name('products.template');
