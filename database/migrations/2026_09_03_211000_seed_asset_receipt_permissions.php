<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = ['asset-receipts.view', 'asset-receipts.create', 'asset-receipts.update', 'asset-receipts.delete', 'asset-receipts.capitalize'];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('Διαχειριστής', 'web')->givePermissionTo($permissions);
        Role::findByName('Χειριστής Παγίων', 'web')->givePermissionTo($permissions);
        Role::findByName('Οικονομική Υπηρεσία', 'web')->givePermissionTo(['asset-receipts.view', 'asset-receipts.capitalize']);
        Role::findByName('Μόνο ανάγνωση', 'web')->givePermissionTo(['asset-receipts.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
