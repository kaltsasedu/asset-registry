<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class CostCenter extends Model
{
    use LogsBusinessActivity;

    protected $fillable = ['code', 'name', 'department_id', 'is_active', 'notes'];
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::deleting(function (self $center): void {
            if ($center->contracts()->exists() || $center->receipts()->exists() || $center->assets()->exists()) {
                throw ValidationException::withMessages(['cost_center' => 'Το κέντρο κόστους χρησιμοποιείται και δεν μπορεί να διαγραφεί. Μπορείτε να το ορίσετε ως ανενεργό.']);
            }
        });
    }

    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function contracts(): HasMany { return $this->hasMany(ProcurementContract::class); }
    public function receipts(): HasMany { return $this->hasMany(AssetReceipt::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
}
