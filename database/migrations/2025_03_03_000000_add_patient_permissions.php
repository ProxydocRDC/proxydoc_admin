<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ajoute les permissions UserPatient et PatientStats en production.
     */
    public function up(): void
    {
        $guardName = config('auth.defaults.guard', 'web');

        $permissions = [
            'widget_PatientStatsWidget',
            'view_any_user::patient',
            'view_user::patient',
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

        $permissionsTable = config('permission.table_names.permissions', 'permissions');
        $roleHasPermissionsTable = config('permission.table_names.role_has_permissions', 'role_has_permissions');

        foreach ($permissions as $name) {
            $exists = DB::table($permissionsTable)
                ->where('name', $name)
                ->where('guard_name', $guardName)
                ->exists();

            if (! $exists) {
                DB::table($permissionsTable)->insert([
                    'name'       => $name,
                    'guard_name' => $guardName,
                ]);
            }
        }

        // Assigner aux rôles super_admin et Admin
        $roleNames = [config('filament-shield.super_admin.name', 'super_admin'), 'Admin'];
        $rolesTable = config('permission.table_names.roles', 'roles');

        foreach ($roleNames as $roleName) {
            $role = DB::table($rolesTable)
                ->where('name', $roleName)
                ->where('guard_name', $guardName)
                ->first();

            if ($role) {
                $permissionIds = DB::table($permissionsTable)
                    ->whereIn('name', $permissions)
                    ->where('guard_name', $guardName)
                    ->pluck('id');

                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table($roleHasPermissionsTable)
                        ->where('permission_id', $permissionId)
                        ->where('role_id', $role->id)
                        ->exists();

                    if (! $exists) {
                        DB::table($roleHasPermissionsTable)->insert([
                            'permission_id' => $permissionId,
                            'role_id'       => $role->id,
                        ]);
                    }
                }
            }
        }

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Ne pas supprimer les permissions pour éviter les conflits
    }
};
