<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class ProcurementContract extends Model
{
    use LogsBusinessActivity;

    public const STATUSES = ['draft' => 'Πρόχειρη', 'active' => 'Ενεργή', 'completed' => 'Ολοκληρωμένη', 'terminated' => 'Λυμένη'];

    protected $fillable = ['contract_number', 'title', 'supplier_id', 'funding_source_id', 'cost_center_id', 'signed_at', 'starts_at', 'ends_at', 'total_amount', 'budget_code', 'ada', 'kimdis_reference', 'decision_number', 'status', 'document_path', 'notes'];
    protected $casts = ['signed_at' => 'date', 'starts_at' => 'date', 'ends_at' => 'date', 'total_amount' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $contract): void {
            if (! $contract->exists) return;

            $used = $contract->capitalized_receipts_total;
            if ((float) $contract->total_amount + 0.005 < $used) {
                throw ValidationException::withMessages(['total_amount' => 'Το συμβατικό ποσό δεν μπορεί να είναι μικρότερο από τις ήδη κεφαλαιοποιημένες παραλαβές (€ ' . number_format($used, 2, ',', '.') . ').']);
            }

            if ($used > 0 && $contract->isDirty(['supplier_id', 'funding_source_id', 'cost_center_id'])) {
                throw ValidationException::withMessages(['contract' => 'Ο προμηθευτής, η χρηματοδότηση και το κέντρο κόστους δεν μεταβάλλονται μετά την κεφαλαιοποίηση παραλαβών.']);
            }
        });

        static::deleting(function (self $contract): void {
            if ($contract->receipts()->exists() || $contract->assets()->exists()) {
                throw ValidationException::withMessages(['contract' => 'Η σύμβαση χρησιμοποιείται σε παραλαβές ή πάγια και δεν μπορεί να διαγραφεί.']);
            }
        });
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function fundingSource(): BelongsTo { return $this->belongsTo(FundingSource::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function receipts(): HasMany { return $this->hasMany(AssetReceipt::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }

    public function getCapitalizedReceiptsTotalAttribute(): float
    {
        return (float) $this->receipts()->where('asset_receipts.status', 'capitalized')
            ->join('asset_receipt_lines', 'asset_receipts.id', '=', 'asset_receipt_lines.asset_receipt_id')
            ->selectRaw('COALESCE(SUM(asset_receipt_lines.quantity * (asset_receipt_lines.unit_value + asset_receipt_lines.vat_amount)), 0) AS aggregate')
            ->value('aggregate');
    }

    public function getRemainingAmountAttribute(): float
    {
        return (float) $this->total_amount - $this->capitalized_receipts_total;
    }
}
