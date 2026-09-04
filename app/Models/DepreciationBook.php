<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DepreciationBook extends Model
{
    use LogsBusinessActivity;
    protected $fillable = ['name', 'code', 'description', 'is_default', 'is_active'];

    protected $casts = ['is_default' => 'boolean', 'is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $book): void {
            if ($book->is_default) {
                static::query()->whereKeyNot($book->id)->update(['is_default' => false]);
            }
        });
    }

    public function periods(): HasMany { return $this->hasMany(DepreciationPeriod::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
    public function depreciationProfiles(): HasMany { return $this->hasMany(AssetDepreciationProfile::class); }
}
