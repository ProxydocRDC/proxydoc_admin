<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Table Columns
    |--------------------------------------------------------------------------
    */

    'column.name' => 'Nom',
    'column.guard_name' => 'Nom du Guard',
    'column.roles' => 'Rôles',
    'column.permissions' => 'Permissions',
    'column.updated_at' => 'Mis à jour à',

    /*
    |--------------------------------------------------------------------------
    | Form Fields
    |--------------------------------------------------------------------------
    */

    'field.name' => 'Nom',
    'field.guard_name' => 'Nom du Guard',
    'field.permissions' => 'Permissions',
    'field.select_all.name' => 'Tout sélectionner',
    'field.select_all.message' => 'Activer toutes les autorisations pour ce rôle',

    /*
    |--------------------------------------------------------------------------
    | Navigation & Resource
    |--------------------------------------------------------------------------
    */

    'nav.group' => 'Sécurité & Accè',
    'nav.role.label' => 'Rôles',
    'nav.role.icon' => 'heroicon-o-shield-check',
    'resource.label.role' => 'Rôle',
    'resource.label.roles' => 'Rôles',

    /*
    |--------------------------------------------------------------------------
    | Section & Tabs
    |--------------------------------------------------------------------------
    */

    'section' => 'Section',
    'resources' => 'Ressources',
    'widgets' => 'Widgets',
    'pages' => 'Pages',
    'custom' => 'Permissions personnalisées',

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */

    'forbidden' => 'Vous n\'avez pas la permission d\'accéder',

    /*
    |--------------------------------------------------------------------------
    | Resource Permissions' Labels
    |--------------------------------------------------------------------------
    */

    // 'resource_permission_prefixes_labels' => [
    //     'view' => 'Voir',
    //     'view_any' => 'Voir tout',
    //     'create' => 'Créer',
    //     'update' => 'Mettre à jour',
    //     'delete' => 'Supprimer',
    //     'delete_any' => 'Supprimer tout',
    //     'force_delete' => 'Forcer la suppression',
    //     'force_delete_any' => 'Forcer la suppression de tout',
    //     'restore' => 'Restaurer',
    //     'replicate' => 'Répliquer',
    //     'reorder' => 'Réordonner',
    //     'restore_any' => 'Restaurer tout',
    // ],
    'resource_permission_prefixes_labels' => [
        'view'      => 'Voir',
        'view_any'  => 'Lister',
        'create'    => 'Créer',
        'update'    => 'Modifier',
        'delete'    => 'Supprimer',
        'delete_any'=> 'Supprimer (lot)',
        'restore'   => 'Restaurer',
        'force_delete'      => 'Supprimer définitivement',
        'force_delete_any'  => 'Supprimer définitivement (lot)',
        'replicate' => 'Dupliquer',
        'reorder'   => 'Réordonner',
        'export'    => 'Exporter',
    ],
    // Entités (ressources/pages/widgets) — adapte à tes slugs
    'resource_permission_prefixes_entities' => [
        'chem::category'          => 'Catégories',
        'chem::manufacturer'      => 'Fabricants',
        'chem::order'             => 'Commandes',
        'chem::order:item'        => 'Lignes de commande',
        'chem::pharmacy'          => 'Pharmacies',
        'chem::pharmacy:product'  => 'Stock pharmacie',
        'chem::posology'          => 'Posologies',
        'chem::product'           => 'Produits',
        'chem::shipment'          => 'Expéditions',
        'chem::shipment:event'    => 'Évènements expédition',
        'chem::supplier'          => 'Fournisseurs',

        'main::city'              => 'Villes',
        'main::country'           => 'Pays',
        'main::currency'          => 'Devises',
        'main::status'            => 'Statuts',
        'main::subscription'      => 'Abonnements',

        'user'                    => 'Utilisateurs',

        // Pages / Widgets si tu veux aussi les renommer
        'pages::shipments_tracker'   => 'Suivi des expéditions',
        'widgets::shipment_map_widget'=> 'Carte des expéditions',
    ],
];
