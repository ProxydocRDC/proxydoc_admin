# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

ProxyDoc Admin — the Filament 5 back-office for the ProxyDoc digital health platform (RDC/DRC market): pharmacies, pharmaceutical products, doctors/appointments, subscriptions, and orders. This is an **admin-only** Laravel app (root route just redirects to the Filament admin login); there is no separate public storefront in this repo. Uses `maatwebsite/excel` and `spatie/simple-excel` for import/export, `league/flysystem-aws-s3-v3` for S3-backed storage.

## Commands

Install dependencies:
```bash
composer install
npm install
```

Dev server (serves app + queue listener + Vite together):
```bash
composer run dev
```
Or individually: `php artisan serve`, `npm run dev`.

Build assets:
```bash
npm run build
```

Run tests:
```bash
php artisan test                      # also runs via `composer test` (clears config first)
php artisan test --filter=TestName
```

Lint (Laravel Pint):
```bash
vendor/bin/pint
```

## Architecture

Two clearly separated domains inside one Filament panel, distinguished by resource prefix:

- **"Chem" domain (pharma/medications)**: `ChemProductResource`, `ChemCategoryResource`, `ChemManufacturerResource`, `ChemPharmaceuticalFormResource`, `ChemPosologyResource`, `ChemPharmacyResource`, `ChemPharmacyProductResource` (pharmacy-specific stock/pricing), `ChemSupplierResource`, `ChemOrderResource`, `ChemHospitalResource`, `ChemShipmentResource`/`ChemShipmentEventResource` (shipment tracking with event history).
- **"Proxy" domain (doctor/appointment platform)**: `ProxyDoctorResource`, `ProxyDoctorAvailabilityResource`, `ProxyDoctorScheduleResource`, `ProxyDoctorServiceResource`, `ProxyPatientResource`, `ProxyAppointmentResource`, `ProxyCategoryResource`, `ProxyServiceResource`, `ProxyPlanFeatureResource`, and reference/lookup resources (`ProxyRefAcademicTitleResource`, `ProxyRefExperienceBandResource`, `ProxyRefHospitalTierResource`).
- **Shared/platform resources**: `SubscriptionPlanResource`/`SubscriptionMemberResource`/`SubscriptionInviteResource` (subscription/membership system), `TransactionResource`, `UserResource`/`UserPatientResource`, `MainZoneResource`/`MainCityResource`/`MainCountryResource`/`MainCurrencyResource`/`MainStatusResource`/`MainPaymentResource` (shared lookup tables), `FeatureResource`, `ParrainesResource` (referral/sponsorship), plus `Shield` resources for roles/permissions (filament-shield-based, consistent with the other Filament apps in this workspace).
- **Bulk import/export**: `app/Imports` and `app/Filament/Imports` handle CSV imports for products, pharmacies, and pharmacy-product stock (see the CSV template routes in `routes/web.php`: `/templates/chem-products.csv`, `/exports/templates/products.csv`, `/templates/pharmacies.csv`, `/templates/pharmacy_products.csv` — each returns a header row + example row so admins can bulk-upload data). `app/Exports` handles the export side (via `maatwebsite/excel`/`spatie/simple-excel`). Failed import reports are downloadable at `/imports/reports/{file}` (auth-protected).
- **Storage**: `app/Support/Storage` plus `league/flysystem-aws-s3-v3` suggest product/pharmacy images are stored on S3-compatible storage; image keys or full URLs are accepted in CSV imports (pipe-separated for multiple images).
- **Static asset routes**: `/js/filament/{path}` and `/css/filament/{path}` manually serve files from `public/js/filament` and `public/css/filament` with explicit content-types — likely a workaround for Filament asset caching/CDN issues.
- **Livewire**: the update route is registered manually in `routes/web.php` (before the fallback route) to avoid the app's catch-all `Route::fallback()` swallowing `/livewire/update` POST requests — keep this ordering in mind if routes are restructured.
- **Authorization**: `app/Policies` (including `Policies/Pages` and `Policies/Widgets`) provide fine-grained access control on top of Filament Shield roles/permissions.
