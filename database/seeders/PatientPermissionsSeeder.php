<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PatientPermissionsSeeder extends Seeder
{
    /**
     * Ajoute les permissions pour la gestion des utilisateurs-patients
     * et le widget "Patients ajoutés (3 derniers jours)".
     */
    public function run(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        $permissions = [
            // Widget : statistique patients 3 derniers jours
            'widget_PatientStatsWidget',

            // Permissions UserPatientResource (liste utilisateurs par statut patient)
            'view_any_user::patient',
            'view_user::patient',

            // Permissions ProxyPatientResource (gestion des patients - format existant)
            'view_any_proxy::patient',
            'view_proxy::patient',
            'create_proxy::patient',
            'update_proxy::patient',
            'restore_proxy::patient',
            'restore_any_proxy::patient',
            'replicate_proxy::patient',
            'reorder_proxy::patient',
            'delete_proxy::patient',
            'delete_any_proxy::patient',
            'force_delete_proxy::patient',
            'force_delete_any_proxy::patient',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guardName],
                ['name' => $name, 'guard_name' => $guardName]
            );
        }

        // Assigner au rôle super_admin
        $superAdmin = config('filament-shield.super_admin.name', 'super_admin');
        $role = Role::where('name', $superAdmin)->where('guard_name', $guardName)->first();

        if ($role) {
            $role->givePermissionTo(
                Permission::whereIn('name', $permissions)
                    ->where('guard_name', $guardName)
                    ->pluck('name')
            );
        }
    }
}
