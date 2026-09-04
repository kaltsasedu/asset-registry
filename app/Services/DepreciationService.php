<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use App\Models\DepreciationEntry;
use App\Models\DepreciationPeriod;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepreciationService
{
    public function preview(DepreciationPeriod $period): Collection
    {
        $date = Carbon::create($period->year, $period->month, 1)->endOfMonth();
        if ($period->status !== 'draft') return $period->entries()->with('asset')->get();
        $period->entries()->where('status', 'draft')->delete();
        $profiles = AssetDepreciationProfile::query()->with('asset')->where('depreciation_book_id', $period->depreciation_book_id)->where('is_active', true)->get();
        foreach ($profiles as $profile) {
            $asset = $profile->asset;
            if (! $asset || in_array($asset->status, ['retired', 'sold'], true) || ! $this->isEligibleForPeriod($profile, $date)) continue;
            $amount = $this->monthlyAmount($profile);
            if ($amount <= 0) continue;
            DepreciationEntry::updateOrCreate(['asset_depreciation_profile_id' => $profile->id, 'depreciation_book_id' => $period->depreciation_book_id, 'year' => $period->year, 'month' => $period->month], [
                'asset_id' => $asset->id,
                'depreciation_period_id' => $period->id, 'amount' => $amount,
                'book_value' => round(max((float) $profile->residual_value, (float) $profile->remaining_value - $amount), 2),
                'status' => 'draft', 'posted_at' => null,
            ]);
        }
        $period->update(['previewed_at' => now()]);
        return $period->entries()->with('asset')->orderBy('asset_id')->get();
    }

    public function post(DepreciationPeriod $period, ?int $userId = null): int
    {
        if ($period->status === 'locked') throw ValidationException::withMessages(['period' => 'Η περίοδος είναι κλειδωμένη και δεν επιδέχεται μεταβολές.']);
        $entries = $this->preview($period);
        FinancialPeriodGuard::duringSystemPosting(function () use ($entries, $period, $userId): void { DB::transaction(function () use ($entries, $period, $userId): void {
            foreach ($entries as $entry) {
                $profile = AssetDepreciationProfile::lockForUpdate()->find($entry->asset_depreciation_profile_id);
                if (! $profile) continue;
                $asset = Asset::lockForUpdate()->find($profile->asset_id);
                if (! $asset) continue;
                $amount = min((float) $entry->amount, max(0, (float) $profile->remaining_value - (float) $profile->residual_value));
                if ($amount <= 0) continue;
                $profile->accumulated_depreciation = round((float) $profile->accumulated_depreciation + $amount, 2);
                $profile->remaining_value = round((float) $asset->purchase_value - (float) $profile->accumulated_depreciation, 2);
                $profile->save();
                $entry->update(['amount' => $amount, 'book_value' => $profile->remaining_value, 'status' => 'posted', 'posted_at' => now()]);
            }
            $period->update(['status' => 'posted', 'posted_at' => now(), 'posted_by' => $userId]);
        }); });
        return $entries->count();
    }

    public function lock(DepreciationPeriod $period): void
    {
        if ($period->status !== 'posted') throw ValidationException::withMessages(['period' => 'Πριν από το κλείδωμα απαιτείται οριστικοποίηση της περιόδου.']);
        $period->update(['status' => 'locked']);
    }

    public function monthlyAmount(AssetDepreciationProfile $profile): float
    {
        if ($profile->depreciation_method === 'none') return 0;
        $available = max(0, (float) $profile->remaining_value - (float) $profile->residual_value);
        if ($available === 0) return 0;
        $rate = max(0, (float) $profile->depreciation_rate) / 100;
        $base = $profile->depreciation_method === 'declining_balance' ? (float) $profile->remaining_value : max(0, (float) $profile->asset->purchase_value - (float) $profile->residual_value);
        return round(min($available, ($base * $rate) / 12), 2);
    }

    private function isEligibleForPeriod(AssetDepreciationProfile $profile, Carbon $periodEnd): bool
    {
        $start = Carbon::parse($profile->depreciation_start_date)->startOfMonth();
        return $profile->depreciation_timing !== 'next_month' || $start->lt($periodEnd->copy()->startOfMonth());
    }
}
