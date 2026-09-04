<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_movements', function (Blueprint $table): void {
            $table->foreignId('delivered_confirmed_by')->nullable()->after('delivered_confirmed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('received_confirmed_by')->nullable()->after('received_confirmed_at')->constrained('users')->nullOnDelete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'asset-movements.submit', 'asset-movements.approve', 'asset-movements.confirm_delivery', 'asset-movements.confirm_receipt', 'asset-movements.complete', 'asset-movements.export',
            'depreciation-books.view', 'depreciation-books.create', 'depreciation-books.update', 'depreciation-books.delete',
            'depreciation-periods.view', 'depreciation-periods.create', 'depreciation-periods.update', 'depreciation-periods.delete', 'depreciation-periods.preview', 'depreciation-periods.post', 'depreciation-periods.lock', 'depreciation-periods.override_locked',
            'asset-maintenances.view', 'asset-maintenances.create', 'asset-maintenances.update', 'asset-maintenances.delete',
            'asset-insurances.view', 'asset-insurances.create', 'asset-insurances.update', 'asset-insurances.delete',
            'saved-asset-searches.view', 'saved-asset-searches.create', 'saved-asset-searches.update', 'saved-asset-searches.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        Role::findByName('Διαχειριστής', 'web')->givePermissionTo($permissions);

        $operator = Role::findByName('Χειριστής Παγίων', 'web');
        $operator->givePermissionTo([
            'asset-movements.submit', 'asset-movements.confirm_delivery', 'asset-movements.confirm_receipt', 'asset-movements.complete', 'asset-movements.export',
            'depreciation-periods.view', 'depreciation-periods.preview',
            'asset-maintenances.view', 'asset-maintenances.create', 'asset-maintenances.update', 'asset-maintenances.delete',
            'asset-insurances.view', 'asset-insurances.create', 'asset-insurances.update', 'asset-insurances.delete',
            'saved-asset-searches.view', 'saved-asset-searches.create', 'saved-asset-searches.update', 'saved-asset-searches.delete',
        ]);

        Role::findByName('Οικονομική Υπηρεσία', 'web')->givePermissionTo([
            'depreciation-books.view', 'depreciation-books.create', 'depreciation-books.update',
            'depreciation-periods.view', 'depreciation-periods.create', 'depreciation-periods.update', 'depreciation-periods.preview', 'depreciation-periods.post', 'depreciation-periods.lock',
            'asset-maintenances.view', 'asset-insurances.view', 'saved-asset-searches.view', 'saved-asset-searches.create', 'saved-asset-searches.update', 'saved-asset-searches.delete',
        ]);

        Role::findByName('Μόνο ανάγνωση', 'web')->givePermissionTo([
            'depreciation-books.view', 'depreciation-periods.view', 'asset-maintenances.view', 'asset-insurances.view', 'saved-asset-searches.view',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('asset_movements', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('delivered_confirmed_by');
            $table->dropConstrainedForeignId('received_confirmed_by');
        });
    }
};
