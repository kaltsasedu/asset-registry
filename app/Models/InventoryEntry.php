<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryEntry extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    protected $fillable = ['inventory_cycle_id', 'asset_id', 'verified_by', 'result', 'actual_location_id', 'notes', 'verified_at'];

    protected $casts = ['verified_at' => 'datetime'];

    public function cycle(): BelongsTo { return $this->belongsTo(InventoryCycle::class, 'inventory_cycle_id'); }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function actualLocation(): BelongsTo { return $this->belongsTo(Location::class, 'actual_location_id'); }
}
