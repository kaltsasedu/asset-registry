<?php
namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetInsurance extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;

    protected $fillable = ['asset_id', 'company', 'policy_number', 'insured_value', 'starts_at', 'expires_at', 'terms', 'document_path'];
    protected $casts = ['insured_value' => 'decimal:2', 'starts_at' => 'date', 'expires_at' => 'date'];
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
}
