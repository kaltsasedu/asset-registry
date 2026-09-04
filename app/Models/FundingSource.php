<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class FundingSource extends Model
{
    use LogsBusinessActivity;

    public const TYPES = [
        'regular_budget' => 'Τακτικός Προϋπολογισμός',
        'public_investment' => 'Πρόγραμμα Δημοσίων Επενδύσεων',
        'european' => 'Ευρωπαϊκή χρηματοδότηση',
        'own_resources' => 'Ίδιοι πόροι',
        'donation' => 'Δωρεά',
        'other' => 'Λοιπή πηγή',
    ];

    protected $fillable = ['code', 'name', 'funding_type', 'program', 'project_code', 'ops_code', 'is_active', 'notes'];
    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::deleting(function (self $source): void {
            if ($source->contracts()->exists() || $source->receipts()->exists() || $source->assets()->exists()) {
                throw ValidationException::withMessages(['funding_source' => 'Η πηγή χρηματοδότησης χρησιμοποιείται και δεν μπορεί να διαγραφεί. Μπορείτε να την ορίσετε ως ανενεργή.']);
            }
        });
    }

    public function contracts(): HasMany { return $this->hasMany(ProcurementContract::class); }
    public function receipts(): HasMany { return $this->hasMany(AssetReceipt::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
}
