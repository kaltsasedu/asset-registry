<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use App\Services\FinancialPeriodGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetDepreciationProfile extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    protected $fillable = ['asset_id', 'depreciation_book_id', 'depreciation_start_date', 'residual_value', 'depreciation_rate', 'depreciation_method', 'depreciation_timing', 'useful_life_years', 'annual_depreciation', 'accumulated_depreciation', 'remaining_value', 'is_active'];
    protected $casts = ['depreciation_start_date' => 'date', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $profile): void {
            app(FinancialPeriodGuard::class)->ensureProfileCanBeSaved($profile);
            $asset = $profile->asset ?? Asset::find($profile->asset_id);
            if (! $asset) return;
            $profile->annual_depreciation = $profile->depreciation_method === 'none' ? 0 : round(((float) $asset->purchase_value - (float) $profile->residual_value) * ((float) $profile->depreciation_rate / 100), 2);
            if ($profile->remaining_value === null) $profile->remaining_value = (float) $asset->purchase_value - (float) $profile->accumulated_depreciation;
        });

        static::saved(function (self $profile): void {
            $asset = $profile->asset ?? Asset::find($profile->asset_id);
            if (! $asset || (int) $asset->depreciation_book_id !== (int) $profile->depreciation_book_id) return;
            $asset->update([
                'depreciation_start_date' => $profile->depreciation_start_date,
                'residual_value' => $profile->residual_value,
                'depreciation_rate' => $profile->depreciation_rate,
                'depreciation_method' => $profile->depreciation_method,
                'depreciation_timing' => $profile->depreciation_timing,
                'useful_life_years' => $profile->useful_life_years,
                'accumulated_depreciation' => $profile->accumulated_depreciation,
            ]);
        });
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function book(): BelongsTo { return $this->belongsTo(DepreciationBook::class, 'depreciation_book_id'); }
    public function entries(): HasMany { return $this->hasMany(DepreciationEntry::class); }
}
