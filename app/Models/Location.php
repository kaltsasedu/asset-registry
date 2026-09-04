<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use LogsBusinessActivity;
    protected $fillable = [
        'name',
        'building',
        'floor',
        'room',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
