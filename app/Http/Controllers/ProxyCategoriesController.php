<?php

namespace App\Http\Controllers;

use App\Models\ChemHospital;
use Illuminate\Http\Request;
use App\Models\proxy_categories;
use App\Support\Storage\S3Upload;
use App\Http\Requests\Storeproxy_categoriesRequest;
use App\Http\Requests\Updateproxy_categoriesRequest;

class ProxyCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(Request $request)
{
    $request->validate(['file' => 'required|file|max:10240']);

    $up = S3Upload::put(
        $request->file('file'),
        'hospitals/pricings',
        public: true // ou false si privé
    );

    // Enregistrer dans la DB (ex: prix public)
    ChemHospital::create([
        'name'         => $request->name,
        'pricing_file' => $up['path'],      // <-- on stocke le PATH
        // eventuellement 'pricing_url' => $up['url'] si tu veux stocker l’URL aussi (pas obligatoire)
    ]);

    // $up['url'] contient le lien public si public=true
}

    /**
     * Display the specified resource.
     */
    public function show(proxy_categories $proxy_categories)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(proxy_categories $proxy_categories)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Updateproxy_categoriesRequest $request, proxy_categories $proxy_categories)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(proxy_categories $proxy_categories)
    {
        //
    }
}
