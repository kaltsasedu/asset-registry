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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('data_scope', 30)->default('all')->after('password');
            $table->boolean('include_responsible_assets')->default(false)->after('data_scope');
        });

        Schema::create('department_user_scope', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'department_id']);
        });

        Schema::create('location_user_scope', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'location_id']);
        });

        Schema::create('asset_user_scope', function (Blueprint $table): void {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'asset_id']);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permission = Permission::findOrCreate('users.manage_data_scope', 'web');
        Role::findOrCreate('Διαχειριστής', 'web')->givePermissionTo($permission);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_user_scope');
        Schema::dropIfExists('location_user_scope');
        Schema::dropIfExists('department_user_scope');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['data_scope', 'include_responsible_assets']);
        });
    }
};
