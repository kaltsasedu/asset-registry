<?php

namespace App\Filament\Resources\Assets\Pages;

use App\Filament\Resources\Assets\AssetResource;
use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use Filament\Actions\DeleteAction;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditAsset extends EditRecord
{
    protected static string $resource = AssetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('dossier_pdf')
                ->label('Φάκελος PDF')
                ->icon('heroicon-o-document-text')
                ->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.dossier_export'))
                ->url(fn (): string => route('assets.dossier.pdf', $this->record))
                ->openUrlInNewTab(),
            Action::make('dossier_zip')
                ->label('Πλήρης Φάκελος ZIP')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.dossier_export'))
                ->url(fn (): string => route('assets.dossier.zip', $this->record)),
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        /** @var Asset $asset */
        $asset = $this->record;
        $profile = AssetDepreciationProfile::firstOrCreate([
            'asset_id' => $asset->id,
            'depreciation_book_id' => $asset->depreciation_book_id,
        ], [
            'depreciation_start_date' => $asset->depreciation_start_date,
            'residual_value' => $asset->residual_value,
            'depreciation_rate' => $asset->depreciation_rate,
            'depreciation_method' => $asset->depreciation_method,
            'depreciation_timing' => $asset->depreciation_timing,
            'useful_life_years' => $asset->useful_life_years,
            'accumulated_depreciation' => $asset->accumulated_depreciation,
            'remaining_value' => $asset->remaining_value,
        ]);
        $profile->fill([
            'depreciation_start_date' => $asset->depreciation_start_date,
            'residual_value' => $asset->residual_value,
            'depreciation_rate' => $asset->depreciation_rate,
            'depreciation_method' => $asset->depreciation_method,
            'depreciation_timing' => $asset->depreciation_timing,
            'useful_life_years' => $asset->useful_life_years,
        ])->save();
    }
}
