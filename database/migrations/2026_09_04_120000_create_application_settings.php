<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('organization_name', 200);
            $table->string('tax_id', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('show_logo_on_reports')->default(true);
            $table->text('report_footer')->nullable();
            $table->string('asset_code_prefix', 20)->default('PAG');
            $table->unsignedTinyInteger('asset_code_digits')->default(6);
            $table->unsignedSmallInteger('warranty_warning_days')->default(90);
            $table->unsignedSmallInteger('insurance_warning_days')->default(30);
            $table->unsignedSmallInteger('maintenance_warning_days')->default(30);
            $table->unsignedSmallInteger('receipt_overdue_days')->default(30);
            $table->timestamps();
        });

        DB::table('application_settings')->insert([
            'organization_name' => config('app.name', 'Μητρώο Παγίων'),
            'show_logo_on_reports' => true,
            'asset_code_prefix' => 'PAG',
            'asset_code_digits' => 6,
            'warranty_warning_days' => 90,
            'insurance_warning_days' => 30,
            'maintenance_warning_days' => 30,
            'receipt_overdue_days' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('application-settings.manage', 'web');
        Role::query()->where('guard_name', 'web')->where('name', 'Διαχειριστής')->get()->each(fn (Role $role) => $role->givePermissionTo($permission));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('application_settings');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
