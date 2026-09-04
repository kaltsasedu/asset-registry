<?php

namespace App\Models\Concerns;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

trait BelongsToVisibleAsset
{
    protected static function bootBelongsToVisibleAsset(): void
    {
        static::addGlobalScope('visible_asset', function (Builder $query): void {
            $user = auth()->user();

            if ($user instanceof User && ! $user->hasFullDataAccess()) {
                $query->whereHas('asset');
            }
        });

        static::saving(function ($record): void {
            $user = auth()->user();

            if (! $record->asset_id || ! $user instanceof User || $user->hasFullDataAccess()) {
                return;
            }

            if (! Asset::query()->whereKey($record->asset_id)->exists()) {
                throw ValidationException::withMessages([
                    'asset_id' => 'Δεν έχετε πρόσβαση στο επιλεγμένο πάγιο.',
                ]);
            }
        });
    }
}
