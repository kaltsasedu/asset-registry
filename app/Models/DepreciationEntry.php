<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationEntry extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    protected $fillable = ['asset_id', 'asset_depreciation_profile_id', 'depreciation_book_id', 'depreciation_period_id', 'year', 'month', 'amount', 'book_value', 'status', 'posted_at'];

    protected $casts = ['amount' => 'decimal:2', 'book_value' => 'decimal:2', 'posted_at' => 'datetime'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function book(): BelongsTo { return $this->belongsTo(DepreciationBook::class, 'depreciation_book_id'); }
    public function period(): BelongsTo { return $this->belongsTo(DepreciationPeriod::class, 'depreciation_period_id'); }
    public function profile(): BelongsTo { return $this->belongsTo(AssetDepreciationProfile::class, 'asset_depreciation_profile_id'); }
}
