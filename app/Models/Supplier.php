<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use LogsBusinessActivity;
    protected $fillable = [
        'name',
        'afm',
        'email',
        'phone',
        'address',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function contracts(): HasMany { return $this->hasMany(ProcurementContract::class); }
    public function receipts(): HasMany { return $this->hasMany(AssetReceipt::class); }
}
