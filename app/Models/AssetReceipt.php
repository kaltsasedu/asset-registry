<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetReceipt extends Model
{
    use LogsBusinessActivity;

    public const STATUSES = ['draft' => 'Πρόχειρη', 'capitalized' => 'Κεφαλαιοποιημένη'];

    protected $fillable = ['receipt_number', 'receipt_date', 'supplier_id', 'procurement_contract_id', 'funding_source_id', 'cost_center_id', 'contract_number', 'contract_date', 'invoice_number', 'invoice_date', 'acceptance_protocol_number', 'acceptance_protocol_date', 'invoice_file_path', 'acceptance_protocol_file_path', 'contract_file_path', 'status', 'capitalized_at', 'capitalized_by', 'created_by', 'notes'];
    protected $casts = ['receipt_date' => 'date', 'contract_date' => 'date', 'invoice_date' => 'date', 'acceptance_protocol_date' => 'date', 'capitalized_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $receipt): void {
            if (! $receipt->receipt_number) {
                $receipt->receipt_number = 'PAR-' . str_pad((string) ((self::max('id') ?? 0) + 1), 6, '0', STR_PAD_LEFT);
            }
            $receipt->created_by ??= auth()->id();
        });
    }

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function procurementContract(): BelongsTo { return $this->belongsTo(ProcurementContract::class); }
    public function fundingSource(): BelongsTo { return $this->belongsTo(FundingSource::class); }
    public function costCenter(): BelongsTo { return $this->belongsTo(CostCenter::class); }
    public function lines(): HasMany { return $this->hasMany(AssetReceiptLine::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function capitalizer(): BelongsTo { return $this->belongsTo(User::class, 'capitalized_by'); }

    public function getTotalAmountAttribute(): float
    {
        return (float) $this->lines()->selectRaw('COALESCE(SUM(quantity * (unit_value + vat_amount)), 0) AS aggregate')->value('aggregate');
    }
}
