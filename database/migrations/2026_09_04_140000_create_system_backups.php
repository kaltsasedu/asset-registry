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
        Schema::table('application_settings', function (Blueprint $table): void {
            $table->boolean('backup_enabled')->default(true);
            $table->string('backup_frequency', 20)->default('daily');
            $table->time('backup_time')->default('02:00:00');
            $table->unsignedSmallInteger('backup_retention_days')->default(30);
            $table->boolean('backup_include_files')->default(true);
        });

        Schema::create('system_backups', function (Blueprint $table): void {
            $table->id();
            $table->string('file_name')->unique();
            $table->string('file_path')->unique();
            $table->boolean('includes_database')->default(true);
            $table->boolean('includes_files')->default(true);
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum_sha256', 64)->nullable();
            $table->string('status', 20)->default('running')->index();
            $table->string('integrity_status', 20)->default('unknown')->index();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('restored_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view', 'create', 'download', 'verify', 'restore', 'delete'] as $action) {
            $permission = Permission::findOrCreate('system-backups.' . $action, 'web');
            Role::query()->where('guard_name', 'web')->where('name', 'Διαχειριστής')->get()
                ->each(fn (Role $role) => $role->givePermissionTo($permission));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('system_backups');

        Schema::table('application_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'backup_enabled', 'backup_frequency', 'backup_time',
                'backup_retention_days', 'backup_include_files',
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
