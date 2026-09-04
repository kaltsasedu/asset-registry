<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Services\FinancialPeriodGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepreciationPeriod extends Model
{
    use LogsBusinessActivity;
    public const STATUSES = ['draft' => 'Πρόχειρη', 'posted' => 'Οριστικοποιημένη', 'locked' => 'Κλειδωμένη'];

    protected $fillable = ['depreciation_book_id', 'year', 'month', 'status', 'previewed_at', 'posted_at', 'posted_by', 'notes'];
    protected $casts = ['previewed_at' => 'datetime', 'posted_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn (self $period) => app(FinancialPeriodGuard::class)->ensurePeriodCanBeChanged($period));
        static::deleting(fn (self $period) => app(FinancialPeriodGuard::class)->ensurePeriodCanBeChanged($period));
    }

    public function book(): BelongsTo { return $this->belongsTo(DepreciationBook::class, 'depreciation_book_id'); }
    public function entries(): HasMany { return $this->hasMany(DepreciationEntry::class); }
    public function postedBy(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }
}
