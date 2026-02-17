<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Modèles à afficher dans la Corbeille (status = 0)
    |--------------------------------------------------------------------------
    | Chaque entrée définit un modèle avec ses colonnes d'affichage.
    */
    'models' => [
        \App\Models\User::class => [
            'label'   => 'Utilisateurs',
            'columns' => ['firstname', 'lastname', 'email', 'phone', 'created_at'],
        ],
        \App\Models\ChemCategory::class => [
            'label'   => 'Catégories produits',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemProduct::class => [
            'label'   => 'Produits',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemSupplier::class => [
            'label'   => 'Fournisseurs',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemOrder::class => [
            'label'   => 'Commandes',
            'columns' => ['id', 'order_status', 'created_at'],
        ],
        \App\Models\ChemHospital::class => [
            'label'   => 'Hôpitaux',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemPharmacy::class => [
            'label'   => 'Pharmacies',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemManufacturer::class => [
            'label'   => 'Fabricants',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemPosology::class => [
            'label'   => 'Posologies',
            'columns' => ['posology_text', 'target_population', 'created_at'],
        ],
        \App\Models\ChemShipment::class => [
            'label'   => 'Livraisons',
            'columns' => ['tracking_number', 'status', 'created_at'],
        ],
        \App\Models\ChemShipmentEvent::class => [
            'label'   => 'Événements livraison',
            'columns' => ['event_time', 'type', 'created_at'],
        ],
        \App\Models\ChemPharmaceuticalForm::class => [
            'label'   => 'Formes pharmaceutiques',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\ChemPharmacyProduct::class => [
            'label'   => 'Produits pharmacie',
            'columns' => ['sku', 'price', 'created_at'],
        ],
        \App\Models\Feature::class => [
            'label'   => 'Fonctionnalités',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\SubscriptionPlan::class => [
            'label'   => 'Plans abonnement',
            'columns' => ['name', 'type', 'created_at'],
        ],
        \App\Models\SubscriptionMember::class => [
            'label'   => 'Membres abonnement',
            'columns' => ['role', 'member_type', 'created_at'],
        ],
        \App\Models\SubscriptionInvite::class => [
            'label'   => 'Invitations',
            'columns' => ['email', 'role', 'created_at'],
        ],
        \App\Models\UserSubscription::class => [
            'label'   => 'Abonnements utilisateurs',
            'columns' => ['subscription_status', 'start_date', 'end_date'],
        ],
        \App\Models\ProxyPlanFeature::class => [
            'label'   => 'Fonctionnalités des plans',
            'columns' => ['status', 'created_at'],
        ],
        \App\Models\ProxyRefAcademicTitle::class => [
            'label'   => 'Titres académiques',
            'columns' => ['label', 'code', 'created_at'],
        ],
        \App\Models\ProxyRefExperienceBand::class => [
            'label'   => 'Tranches d\'expérience',
            'columns' => ['label', 'min_years', 'max_years', 'created_at'],
        ],
        \App\Models\ProxyRefHospitalTier::class => [
            'label'   => 'Niveaux d\'hôpitaux',
            'columns' => ['label', 'code', 'created_at'],
        ],
        \App\Models\ProxyPatient::class => [
            'label'   => 'Patients',
            'columns' => ['fullname', 'birthdate', 'created_at'],
        ],
        \App\Models\ProxyService::class => [
            'label'   => 'Services',
            'columns' => ['label', 'code', 'created_at'],
        ],
        \App\Models\ProxyDoctor::class => [
            'label'   => 'Médecins',
            'columns' => ['user.firstname', 'user.lastname', 'created_at'],
        ],
        \App\Models\ProxyDoctorSchedule::class => [
            'label'   => 'Agendas médecins',
            'columns' => ['name', 'valid_from', 'valid_to', 'created_at'],
        ],
        \App\Models\ProxyDoctorService::class => [
            'label'   => 'Affectations médecin/service',
            'columns' => ['created_at'],
        ],
        \App\Models\ProxyDoctorAvailability::class => [
            'label'   => 'Disponibilités',
            'columns' => ['weekday', 'start_time', 'end_time', 'created_at'],
        ],
        \App\Models\ProxyAppointment::class => [
            'label'   => 'Rendez-vous',
            'columns' => ['appointment_status', 'created_at'],
        ],
        \App\Models\proxy_categories::class => [
            'label'   => 'Catégories proxy',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\MainStatus::class => [
            'label'   => 'Statuts',
            'columns' => ['status_name', 'created_at'],
        ],
        \App\Models\MainZone::class => [
            'label'   => 'Zones',
            'columns' => ['name', 'city', 'created_at'],
        ],
        \App\Models\MainCity::class => [
            'label'   => 'Villes',
            'columns' => ['city', 'created_at'],
        ],
        \App\Models\MainCountry::class => [
            'label'   => 'Pays',
            'columns' => ['name_fr', 'code', 'created_at'],
        ],
        \App\Models\MainCurrency::class => [
            'label'   => 'Monnaies',
            'columns' => ['name', 'code', 'created_at'],
        ],
        \App\Models\MainPayment::class => [
            'label'   => 'Paiements',
            'columns' => ['amount', 'currency', 'payment_status', 'created_at'],
        ],
    ],
];
