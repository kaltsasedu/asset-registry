<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetReceiptLine extends Model
{
    use LogsBusinessActivity;

    protected $fillable = ['asset_receipt_id', 'description', 'quantity', 'unit_value', 'vat_amount', 'asset_category_id', 'depreciation_book_id', 'location_id', 'department_id', 'asset_custodian_id', 'serial_number', 'manufacturer', 'model', 'depreciation_start_date', 'residual_value', 'depreciation_rate', 'depreciation_method', 'depreciation_timing', 'useful_life_years', 'notes'];
    protected $casts = ['depreciation_start_date' => 'date', 'quantity' => 'integer', 'unit_value' => 'decimal:2', 'vat_amount' => 'decimal:2', 'residual_value' => 'decimal:2', 'depreciation_rate' => 'decimal:2', 'useful_life_years' => 'integer'];

    public function receipt(): BelongsTo { return $this->belongsTo(AssetReceipt::class, 'asset_receipt_id'); }
    public function category(): BelongsTo { return $this->belongsTo(AssetCategory::class, 'asset_category_id'); }
    public function depreciationBook(): BelongsTo { return $this->belongsTo(DepreciationBook::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function custodian(): BelongsTo { return $this->belongsTo(AssetCustodian::class, 'asset_custodian_id'); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
}
