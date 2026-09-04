<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $names = [
            'assets.view', 'assets.create', 'assets.update', 'assets.delete',
            'asset-movements.view', 'asset-movements.create', 'asset-movements.update', 'asset-movements.delete',
            'suppliers.manage', 'asset-categories.manage', 'locations.manage', 'departments.manage',
            'users.manage', 'roles.manage',
        ];

        DB::table('permissions')->upsert(
            array_map(fn (string $name): array => [
                'name' => $name,
                'guard_name' => 'web',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ], $names),
            ['name', 'guard_name'],
            ['updated_at'],
        );
    }

    public function down(): void
    {
        DB::table('permissions')->whereIn('name', [
            'assets.view', 'assets.create', 'assets.update', 'assets.delete',
            'asset-movements.view', 'asset-movements.create', 'asset-movements.update', 'asset-movements.delete',
            'suppliers.manage', 'asset-categories.manage', 'locations.manage', 'departments.manage',
            'users.manage', 'roles.manage',
        ])->delete();
    }
};
