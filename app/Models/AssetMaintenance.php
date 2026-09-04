<?php
namespace App\Models;

use App\Models\Concerns\LogsBusinessActivity;
use App\Models\Concerns\BelongsToVisibleAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetMaintenance extends Model
{
    use LogsBusinessActivity, BelongsToVisibleAsset;

    public const TYPES = ['preventive' => 'Προληπτική', 'corrective' => 'Επισκευή', 'inspection' => 'Έλεγχος'];
    public const STATUSES = ['scheduled' => 'Προγραμματισμένη', 'in_progress' => 'Σε εξέλιξη', 'completed' => 'Ολοκληρώθηκε', 'cancelled' => 'Ακυρώθηκε'];
    protected $fillable = ['asset_id', 'maintenance_type', 'status', 'title', 'description', 'scheduled_at', 'performed_at', 'interval_months', 'next_service_at', 'generated_from_id', 'technician', 'assigned_to_user_id', 'cost', 'document_number', 'notes'];
    protected $casts = ['scheduled_at' => 'date', 'performed_at' => 'date', 'next_service_at' => 'date', 'cost' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(function (self $maintenance): void {
            if ($maintenance->status === 'completed' && ! $maintenance->next_service_at && $maintenance->performed_at && $maintenance->interval_months) {
                $maintenance->next_service_at = $maintenance->performed_at->copy()->addMonths($maintenance->interval_months);
            }
        });

        static::saved(function (self $maintenance): void {
            if ($maintenance->status !== 'completed' || ! $maintenance->performed_at || ! $maintenance->interval_months || self::query()->where('generated_from_id', $maintenance->id)->exists()) {
                return;
            }

            self::create([
                'asset_id' => $maintenance->asset_id,
                'maintenance_type' => $maintenance->maintenance_type,
                'status' => 'scheduled',
                'title' => $maintenance->title,
                'description' => $maintenance->description,
                'scheduled_at' => $maintenance->performed_at->copy()->addMonths($maintenance->interval_months),
                'interval_months' => $maintenance->interval_months,
                'generated_from_id' => $maintenance->id,
                'technician' => $maintenance->technician,
                'assigned_to_user_id' => $maintenance->assigned_to_user_id,
                'notes' => 'Δημιουργήθηκε αυτόματα από ολοκληρωμένη επαναλαμβανόμενη συντήρηση.',
            ]);
        });
    }

    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to_user_id'); }
    public function generatedFrom(): BelongsTo { return $this->belongsTo(self::class, 'generated_from_id'); }
    public function generatedMaintenance(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(self::class, 'generated_from_id'); }
}
