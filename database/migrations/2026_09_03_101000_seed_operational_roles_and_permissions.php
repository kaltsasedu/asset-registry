<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'assets.view', 'assets.create', 'assets.update', 'assets.delete', 'assets.export', 'assets.status_change',
            'asset-movements.view', 'asset-movements.create', 'asset-movements.update', 'asset-movements.delete',
            'inventory.view', 'inventory.create', 'inventory.update', 'inventory.delete', 'inventory.perform',
            'asset-documents.view', 'asset-documents.create', 'asset-documents.update', 'asset-documents.delete',
            'asset-disposals.view', 'asset-disposals.create',
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'asset-categories.view', 'asset-categories.create', 'asset-categories.update', 'asset-categories.delete',
            'locations.view', 'locations.create', 'locations.update', 'locations.delete',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'asset-custodians.view', 'asset-custodians.create', 'asset-custodians.update', 'asset-custodians.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'activity-logs.view',
        ];

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $allRegistry = [
            'suppliers.view', 'suppliers.create', 'suppliers.update', 'suppliers.delete',
            'asset-categories.view', 'asset-categories.create', 'asset-categories.update', 'asset-categories.delete',
            'locations.view', 'locations.create', 'locations.update', 'locations.delete',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'asset-custodians.view', 'asset-custodians.create', 'asset-custodians.update', 'asset-custodians.delete',
        ];

        $roles = [
            'Διαχειριστής' => $permissions,
            'Χειριστής Παγίων' => array_merge([
                'assets.view', 'assets.create', 'assets.update', 'assets.delete', 'assets.export', 'assets.status_change',
                'asset-movements.view', 'asset-movements.create', 'asset-movements.update', 'asset-movements.delete',
                'inventory.view', 'inventory.create', 'inventory.update', 'inventory.perform',
                'asset-documents.view', 'asset-documents.create', 'asset-documents.update', 'asset-documents.delete',
                'asset-disposals.view', 'asset-disposals.create',
            ], $allRegistry),
            'Απογραφέας' => ['assets.view', 'inventory.view', 'inventory.perform', 'asset-documents.view'],
            'Οικονομική Υπηρεσία' => ['assets.view', 'assets.export', 'asset-documents.view', 'asset-disposals.view', 'asset-disposals.create', 'suppliers.view', 'asset-categories.view', 'activity-logs.view'],
            'Μόνο ανάγνωση' => ['assets.view', 'asset-movements.view', 'inventory.view', 'asset-documents.view', 'asset-disposals.view', 'suppliers.view', 'asset-categories.view', 'locations.view', 'departments.view', 'asset-custodians.view'],
        ];

        foreach ($roles as $name => $rolePermissions) {
            Role::findOrCreate($name, 'web')->syncPermissions($rolePermissions);
        }

        // Διατηρείται η δυνατότητα διαχείρισης της ήδη εγκατεστημένης εφαρμογής.
        $firstUser = User::query()->orderBy('id')->first();
        if ($firstUser && $firstUser->roles()->doesntExist()) {
            $firstUser->assignRole('Διαχειριστής');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Οι ρόλοι και τα δικαιώματα διατηρούνται ως επιχειρησιακά δεδομένα.
    }
};
