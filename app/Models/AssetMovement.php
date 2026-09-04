<?php

namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class AssetMovement extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;
    protected $fillable = [
        'asset_id',
        'from_location_id',
        'to_location_id',
        'from_department_id',
        'to_department_id',
        'from_custodian_id',
        'to_custodian_id',
        'movement_date',
        'reason',
        'status',
        'submitted_at',
        'approved_at',
        'approved_by',
        'delivered_at',
        'received_at',
        'delivered_by_name',
        'received_by_name',
        'delivered_confirmed_at',
        'delivered_confirmed_by',
        'received_confirmed_at',
        'received_confirmed_by',
        'notes',
    ];

    protected $casts = [
        'movement_date' => 'date',
        'delivered_at' => 'date',
        'received_at' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'delivered_confirmed_at' => 'datetime',
        'received_confirmed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(function (self $movement): void {
            if ($movement->status !== 'completed' || ! $movement->asset) return;
            $changes = array_filter([
                'location_id' => $movement->to_location_id,
                'department_id' => $movement->to_department_id,
                'asset_custodian_id' => $movement->to_custodian_id,
            ], fn ($value) => $value !== null);
            if ($changes !== []) $movement->asset->update($changes);
        });

        static::saving(function (self $movement): void {
            if ($movement->exists && $movement->getOriginal('status') === 'completed' && $movement->isDirty()) {
                throw ValidationException::withMessages([
                    'movement' => 'Η ολοκληρωμένη διακίνηση είναι αμετάβλητη. Για διόρθωση απαιτείται νέα αντιλογιστική διακίνηση.',
                ]);
            }

            if ($movement->isDirty('status') && $movement->status === 'completed') {
                if ($movement->getOriginal('status') !== 'approved' || ! $movement->received_confirmed_at) {
                    throw ValidationException::withMessages([
                        'movement' => 'Η ολοκλήρωση επιτρέπεται μόνο μετά από έγκριση και ηλεκτρονική επιβεβαίωση παραλαβής.',
                    ]);
                }
            }
        });

        static::deleting(function (self $movement): void {
            if ($movement->status === 'completed') {
                throw ValidationException::withMessages([
                    'movement' => 'Η ολοκληρωμένη διακίνηση δεν διαγράφεται.',
                ]);
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(
            Location::class,
            'from_location_id'
        );
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(
            Location::class,
            'to_location_id'
        );
    }

    public function fromDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'from_department_id'); }
    public function toDepartment(): BelongsTo { return $this->belongsTo(Department::class, 'to_department_id'); }
    public function fromCustodian(): BelongsTo { return $this->belongsTo(AssetCustodian::class, 'from_custodian_id'); }
    public function toCustodian(): BelongsTo { return $this->belongsTo(AssetCustodian::class, 'to_custodian_id'); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function deliveryConfirmer(): BelongsTo { return $this->belongsTo(User::class, 'delivered_confirmed_by'); }
    public function receiptConfirmer(): BelongsTo { return $this->belongsTo(User::class, 'received_confirmed_by'); }
}
