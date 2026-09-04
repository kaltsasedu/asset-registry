<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AssetDataScope
{
    /** @var array<int, array{departments: array<int>, locations: array<int>, assets: array<int>, responsible: bool}> */
    private static array $rules = [];

    public function apply(Builder $query, ?User $user): Builder
    {
        if (! $user || $user->hasFullDataAccess()) {
            return $query;
        }

        $rules = $this->rules($user);

        if ($rules['departments'] === [] && $rules['locations'] === [] && $rules['assets'] === [] && ! $rules['responsible']) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visible) use ($rules, $user): void {
            if ($rules['departments'] !== []) {
                $visible->orWhereIn('assets.department_id', $rules['departments']);
            }

            if ($rules['locations'] !== []) {
                $visible->orWhereIn('assets.location_id', $rules['locations']);
            }

            if ($rules['assets'] !== []) {
                $visible->orWhereIn('assets.id', $rules['assets']);
            }

            if ($rules['responsible']) {
                $visible->orWhereHas('custodian', fn (Builder $custodian) => $custodian->where('user_id', $user->id));
            }
        });
    }

    public function canAccess(Asset $asset, ?User $user): bool
    {
        if (! $user || $user->hasFullDataAccess()) {
            return $user !== null;
        }

        $rules = $this->rules($user);

        return ($asset->id && in_array((int) $asset->id, $rules['assets'], true))
            || ($asset->department_id && in_array((int) $asset->department_id, $rules['departments'], true))
            || ($asset->location_id && in_array((int) $asset->location_id, $rules['locations'], true))
            || ($rules['responsible'] && $asset->asset_custodian_id && $asset->custodian()->where('user_id', $user->id)->exists());
    }

    public function canCreateAssets(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasFullDataAccess()) {
            return true;
        }

        $rules = $this->rules($user);

        return $rules['departments'] !== [] || $rules['locations'] !== [] || $rules['responsible'];
    }

    /** @return array{departments: array<int>, locations: array<int>, assets: array<int>, responsible: bool} */
    private function rules(User $user): array
    {
        return self::$rules[$user->id] ??= [
            'departments' => DB::table('department_user_scope')->where('user_id', $user->id)->pluck('department_id')->map(fn ($id) => (int) $id)->all(),
            'locations' => DB::table('location_user_scope')->where('user_id', $user->id)->pluck('location_id')->map(fn ($id) => (int) $id)->all(),
            'assets' => DB::table('asset_user_scope')->where('user_id', $user->id)->pluck('asset_id')->map(fn ($id) => (int) $id)->all(),
            'responsible' => (bool) $user->include_responsible_assets,
        ];
    }
}
