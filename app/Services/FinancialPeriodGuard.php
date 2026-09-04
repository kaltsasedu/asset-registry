<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use App\Models\DepreciationPeriod;
use Illuminate\Validation\ValidationException;

class FinancialPeriodGuard
{
    private static int $systemUpdateDepth = 0;
    private const ASSET_FIELDS = [
        'depreciation_book_id', 'purchase_date', 'depreciation_start_date',
        'purchase_value', 'residual_value', 'depreciation_rate',
        'depreciation_method', 'depreciation_timing', 'useful_life_years',
        'accumulated_depreciation',
    ];

    private const PROFILE_FIELDS = [
        'depreciation_book_id', 'depreciation_start_date', 'residual_value',
        'depreciation_rate', 'depreciation_method', 'depreciation_timing',
        'useful_life_years', 'accumulated_depreciation', 'remaining_value', 'is_active',
    ];

    public function ensureAssetCanBeSaved(Asset $asset): void
    {
        if (self::$systemUpdateDepth > 0) {
            return;
        }
        if (! $asset->exists || ! $this->hasChanges($asset, self::ASSET_FIELDS)) {
            return;
        }

        $bookId = $asset->getOriginal('depreciation_book_id') ?: $asset->depreciation_book_id;
        $this->ensureBookCanChange($bookId);
    }

    public function ensureProfileCanBeSaved(AssetDepreciationProfile $profile): void
    {
        if (self::$systemUpdateDepth > 0) {
            return;
        }
        if (! $profile->exists || ! $this->hasChanges($profile, self::PROFILE_FIELDS)) {
            return;
        }

        $this->ensureBookCanChange($profile->getOriginal('depreciation_book_id') ?: $profile->depreciation_book_id);
    }

    /** Επιτρέπει μόνο στις ελεγχόμενες υπηρεσίες οριστικοποίησης να ενημερώνουν τρέχοντα υπόλοιπα. */
    public static function duringSystemPosting(\Closure $callback): mixed
    {
        self::$systemUpdateDepth++;

        try {
            return $callback();
        } finally {
            self::$systemUpdateDepth--;
        }
    }

    public function ensurePeriodCanBeChanged(DepreciationPeriod $period): void
    {
        if ($period->exists && $period->getOriginal('status') === 'locked' && $period->isDirty()) {
            throw ValidationException::withMessages([
                'period' => 'Η κλειδωμένη περίοδος δεν επιδέχεται μεταβολές.',
            ]);
        }
    }

    private function ensureBookCanChange(?int $bookId): void
    {
        if (! $bookId || ! DepreciationPeriod::query()->where('depreciation_book_id', $bookId)->where('status', 'locked')->exists()) {
            return;
        }

        $user = auth()->user();
        if ($user?->hasRole('Διαχειριστής') || $user?->can('depreciation-periods.override_locked')) {
            return;
        }

        throw ValidationException::withMessages([
            'financial_period' => 'Υπάρχει κλειδωμένη περίοδος αποσβέσεων. Η οικονομική αλλαγή απαιτεί ειδικό δικαίωμα παράκαμψης.',
        ]);
    }

    private function hasChanges(object $model, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($model->isDirty($field)) {
                return true;
            }
        }

        return false;
    }
}
