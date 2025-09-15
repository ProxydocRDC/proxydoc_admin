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
Route::get('/exports/templates/products.csv', function () {
    $headers = [
        'name','generic_name','brand_name','price_ref',
        'category_code','manufacturer_name','form_name',
        'sku','barcode','strength','dosage','unit',
        'stock','min_stock','price_sale','price_purchase',
        'description','is_active',
    ];

    $content = implode(',', $headers)."\n";
    return response($content, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="template_products.csv"',
    ]);
})->name('products.template');
