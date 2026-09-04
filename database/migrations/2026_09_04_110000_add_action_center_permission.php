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

        $permission = Permission::findOrCreate('action-center.view', 'web');

        Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ['Διαχειριστής', 'Χειριστής Παγίων', 'Απογραφέας', 'Οικονομική Υπηρεσία', 'Μόνο ανάγνωση'])
            ->get()
            ->each(fn (Role $role) => $role->givePermissionTo($permission));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
