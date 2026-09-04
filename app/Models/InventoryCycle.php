<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryCycle extends Model
{
    use LogsBusinessActivity;
    protected $fillable = ['name', 'starts_at', 'ends_at', 'location_id', 'department_id', 'created_by', 'status'];

    protected $casts = ['starts_at' => 'date', 'ends_at' => 'date'];

    public function entries(): HasMany { return $this->hasMany(InventoryEntry::class); }
    public function location(): BelongsTo { return $this->belongsTo(Location::class); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
