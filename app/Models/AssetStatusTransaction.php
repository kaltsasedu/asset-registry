<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetStatusTransaction extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    public const STATUSES = [
        'under_receipt' => 'Υπό παραλαβή',
        'in_use' => 'Σε χρήση',
        'under_repair' => 'Σε επισκευή',
        'in_inventory' => 'Σε απογραφή',
        'pending_disposal' => 'Προς απόσυρση',
        'retired' => 'Αποσυρμένο',
        'sold' => 'Πωλημένο',
    ];

    public const DISPOSAL_TYPES = [
        'retirement' => 'Απόσυρση',
        'sale' => 'Πώληση',
        'destruction' => 'Καταστροφή',
        'donation' => 'Δωρεά',
    ];

    public const TYPES = [
        'initial_registration' => 'Αρχική καταχώριση',
        'status_change' => 'Μεταβολή κατάστασης',
        'retirement' => 'Απόσυρση',
        'sale' => 'Πώληση',
        'destruction' => 'Καταστροφή',
        'donation' => 'Δωρεά',
    ];

    protected $fillable = [
        'asset_id', 'transaction_type', 'from_status', 'to_status', 'reason',
        'document_number', 'document_date', 'document_path', 'purchase_value',
        'accumulated_depreciation', 'book_value', 'disposal_proceeds', 'gain_loss',
        'notes', 'created_by',
    ];

    protected $casts = [
        'document_date' => 'date',
        'purchase_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
        'book_value' => 'decimal:2',
        'disposal_proceeds' => 'decimal:2',
        'gain_loss' => 'decimal:2',
    ];

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
