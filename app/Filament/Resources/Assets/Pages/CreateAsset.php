<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\AssetStatusTransaction;
use App\Models\AssetDepreciationProfile;
use Filament\Resources\Pages\CreateRecord;

class CreateAsset extends CreateRecord
{
    protected static string $resource = AssetResource::class;

    protected function afterCreate(): void
    {
        /** @var Asset $asset */
        $asset = $this->record;

        AssetDepreciationProfile::create([
            'asset_id' => $asset->id,
            'depreciation_book_id' => $asset->depreciation_book_id,
            'depreciation_start_date' => $asset->depreciation_start_date,
            'residual_value' => $asset->residual_value,
            'depreciation_rate' => $asset->depreciation_rate,
            'depreciation_method' => $asset->depreciation_method,
            'depreciation_timing' => $asset->depreciation_timing,
            'useful_life_years' => $asset->useful_life_years,
            'accumulated_depreciation' => $asset->accumulated_depreciation,
            'remaining_value' => $asset->remaining_value,
        ]);

        AssetStatusTransaction::create([
            'asset_id' => $asset->id,
            'transaction_type' => 'initial_registration',
            'to_status' => $asset->status,
            'reason' => 'Αρχική καταχώριση παγίου.',
            'purchase_value' => $asset->purchase_value,
            'accumulated_depreciation' => $asset->accumulated_depreciation,
            'book_value' => $asset->remaining_value,
            'created_by' => auth()->id(),
        ]);
    }
}
