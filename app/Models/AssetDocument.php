<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    public const TYPES = [
        'invoice' => 'Τιμολόγιο',
        'acceptance_protocol' => 'Πρωτόκολλο παραλαβής',
        'warranty' => 'Εγγύηση',
        'photo' => 'Φωτογραφία',
        'contract' => 'Σύμβαση',
        'repair_note' => 'Δελτίο επισκευής',
        'destruction_protocol' => 'Πρωτόκολλο καταστροφής',
        'other' => 'Λοιπό δικαιολογητικό',
    ];

    protected $fillable = ['asset_id', 'document_type', 'title', 'document_number', 'document_date', 'file_path', 'notes', 'created_by'];
    protected $casts = ['document_date' => 'date'];

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
