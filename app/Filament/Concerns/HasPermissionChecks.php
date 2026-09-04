<?php

namespace App\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasPermissionChecks
{
    public static function canPerform(string $action): bool
    {
        $user = auth()->user();

        return $user !== null && (
            $user->hasRole('Διαχειριστής') || $user->can(static::$permissionPrefix . '.' . $action)
        );
    }

    protected static function hasPermission(string $action): bool
    {
        return static::canPerform($action);
    }

    public static function canViewAny(): bool { return static::hasPermission('view'); }
    public static function canCreate(): bool { return static::hasPermission('create'); }
    public static function canEdit(Model $record): bool { return static::hasPermission('update'); }
    public static function canDelete(Model $record): bool { return static::hasPermission('delete'); }
}
