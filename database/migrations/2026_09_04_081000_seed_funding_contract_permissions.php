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

        $groups = ['funding-sources', 'cost-centers', 'procurement-contracts'];
        $permissions = collect($groups)->flatMap(fn (string $group): array => ["{$group}.view", "{$group}.create", "{$group}.update", "{$group}.delete", "{$group}.export"])->push('asset-receipts.export')->all();
        foreach ($permissions as $permission) Permission::findOrCreate($permission, 'web');

        Role::findByName('Διαχειριστής', 'web')->givePermissionTo($permissions);
        Role::findByName('Χειριστής Παγίων', 'web')->givePermissionTo($permissions);
        Role::findByName('Οικονομική Υπηρεσία', 'web')->givePermissionTo($permissions);
        Role::findByName('Μόνο ανάγνωση', 'web')->givePermissionTo(['funding-sources.view', 'cost-centers.view', 'procurement-contracts.view']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
