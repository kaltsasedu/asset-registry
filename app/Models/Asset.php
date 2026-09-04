<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Services\FinancialPeriodGuard;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use App\Services\AssetDataScope;

class Asset extends Model
{
    use LogsActivity;
    protected $fillable = [
        'asset_code',
        'name',
        'description',
        'asset_category_id',
        'asset_receipt_line_id',
        'depreciation_book_id',
        'supplier_id',
        'procurement_contract_id',
        'funding_source_id',
        'cost_center_id',
        'location_id',
        'department_id',
        'asset_custodian_id',
        'serial_number',
        'manufacturer',
        'model',
        'purchase_date',
        'depreciation_start_date',
        'purchase_value',
        'vat_amount',
        'residual_value',
        'depreciation_rate',
        'depreciation_method',
        'depreciation_timing',
        'useful_life_years',
        'accumulated_depreciation',
        'book_value',
        'invoice_number',
        'invoice_date',
        'warranty_expires_at',
        'warranty_starts_at',
        'warranty_terms',
        'warranty_provider',
        'status',
        'annual_depreciation',
        'remaining_value',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'depreciation_start_date' => 'date',
        'invoice_date' => 'date',
        'warranty_expires_at' => 'date',
        'warranty_starts_at' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('visible_to_user', function (Builder $query): void {
            app(AssetDataScope::class)->apply($query, auth()->user());
        });

        static::creating(function ($asset) {

            if ($asset->asset_code) {
                return;
            }

            $last = self::withoutGlobalScope('visible_to_user')->latest('id')->first();

            $nextNumber = $last
                ? $last->id + 1
                : 1;

            $asset->asset_code = ApplicationSetting::current()->assetCode($nextNumber);
        });

        static::saving(function ($asset) {

            if (auth()->user() && ! app(AssetDataScope::class)->canAccess($asset, auth()->user())) {
                throw ValidationException::withMessages([
                    'asset' => 'Το πάγιο βρίσκεται εκτός της επιτρεπόμενης περιοχής δεδομένων σας.',
                ]);
            }

            app(FinancialPeriodGuard::class)->ensureAssetCanBeSaved($asset);

            $asset->annual_depreciation = $asset->depreciation_method === 'none' ? 0 :
                round(
                    (
                        $asset->purchase_value
                        - $asset->residual_value
                    )
                    *
                    (
                        $asset->depreciation_rate / 100
                    ),
                    2
                );

            $asset->remaining_value =
                $asset->purchase_value
                - $asset->accumulated_depreciation;

        });

        static::deleting(function (self $asset): void {
            $hasHistory = $asset->movements()->exists()
                || $asset->depreciationEntries()->exists()
                || $asset->statusTransactions()->exists()
                || $asset->documents()->exists()
                || $asset->inventoryEntries()->exists()
                || $asset->maintenances()->exists()
                || $asset->insurances()->exists();

            if ($hasHistory) {
                throw ValidationException::withMessages([
                    'asset' => 'Το πάγιο έχει ιστορικές εγγραφές και δεν μπορεί να διαγραφεί. Χρησιμοποιήστε απόσυρση ή πώληση.',
                ]);
            }
        });
    }
    
    public function category(): BelongsTo
    {
        return $this->belongsTo(
            AssetCategory::class,
            'asset_category_id'
        );
    }

    public function receiptLine(): BelongsTo { return $this->belongsTo(AssetReceiptLine::class, 'asset_receipt_line_id'); }

    public function depreciationBook(): BelongsTo { return $this->belongsTo(DepreciationBook::class); }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function procurementContract(): BelongsTo { return $this->belongsTo(ProcurementContract::class); }
    public function fundingSource(): BelongsTo { return $this->belongsTo(FundingSource::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
    public function custodian(): BelongsTo { return $this->belongsTo(AssetCustodian::class, 'asset_custodian_id'); }

    public function depreciationEntries(): HasMany
    {
        return $this->hasMany(DepreciationEntry::class);
    }

    public function depreciationProfiles(): HasMany
    {
        return $this->hasMany(AssetDepreciationProfile::class);
    }

    public function inventoryEntries(): HasMany { return $this->hasMany(InventoryEntry::class); }
    public function maintenances(): HasMany { return $this->hasMany(AssetMaintenance::class); }
    public function insurances(): HasMany { return $this->hasMany(AssetInsurance::class); }

    public function statusTransactions(): HasMany
    {
        return $this->hasMany(AssetStatusTransaction::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(AssetMovement::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('Πάγια')
            ->logOnly($this->fillable)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
