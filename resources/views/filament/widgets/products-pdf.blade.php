<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits encodés - {{ $userName }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 20px;
        }
        h1 {
            font-size: 18px;
            margin-bottom: 10px;
            color: #333;
        }
        .info {
            font-size: 9px;
            color: #666;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 8px;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-yes {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-no {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-active {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-required {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-optional {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <h1>Produits encodés par {{ $userName }}</h1>
    <div class="info">
        @if($dateFrom || $dateTo)
            Période : 
            @if($dateFrom)
                Du {{ $dateFrom->format('d/m/Y') }}
            @endif
            @if($dateTo)
                Au {{ $dateTo->format('d/m/Y') }}
            @endif
        @else
            Toutes les périodes
        @endif
        <br>
        Total : {{ $products->count() }} produit(s)
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nom commercial</th>
                <th>DCI</th>
                <th>Marque</th>
                <th>Catégorie</th>
                <th>Fabricant</th>
                <th>Forme galénique</th>
                <th>Dosage</th>
                <th>Conditionnement</th>
                <th>Code ATC</th>
                <th>Prix réf.</th>
                <th>Statut</th>
                <th>Prescription</th>
                <th>Images</th>
                <th>Date d'encodage</th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $index => $product)
                @php
                    $hasImages = false;
                    try {
                        $imageKeys = $product->imageKeys();
                        $hasImages = !empty($imageKeys) && count($imageKeys) > 0;
                    } catch (\Exception $e) {
                        $hasImages = false;
                    }
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $product->name ?? '—' }}</td>
                    <td>{{ $product->generic_name ?? '—' }}</td>
                    <td>{{ $product->brand_name ?? '—' }}</td>
                    <td>{{ $product->category ? $product->category->name : '—' }}</td>
                    <td>{{ $product->manufacturer ? $product->manufacturer->name : '—' }}</td>
                    <td>{{ $product->form ? $product->form->name : '—' }}</td>
                    <td>
                        @if($product->strength)
                            {{ rtrim(rtrim(number_format((float) $product->strength, 2, '.', ''), '0'), '.') }} {{ $product->unit ?? '' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $product->packaging ?? '—' }}</td>
                    <td>{{ $product->atc_code ?? '—' }}</td>
                    <td>
                        @if($product->price_ref)
                            {{ number_format((float) $product->price_ref, 2, '.', ' ') }} {{ $product->currency ?? 'USD' }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if((int)($product->status ?? 0) === 1)
                            <span class="badge badge-active">Actif</span>
                        @else
                            <span class="badge badge-inactive">Inactif</span>
                        @endif
                    </td>
                    <td>
                        @if((int)($product->with_prescription ?? 0) === 1)
                            <span class="badge badge-required">Obligatoire</span>
                        @else
                            <span class="badge badge-optional">Optionelle</span>
                        @endif
                    </td>
                    <td>
                        @if($hasImages)
                            <span class="badge badge-yes">Oui</span>
                        @else
                            <span class="badge badge-no">Non</span>
                        @endif
                    </td>
                    <td>{{ $product->created_at ? $product->created_at->format('d/m/Y H:i') : '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align: center; padding: 20px;">
                        Aucun produit trouvé
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
