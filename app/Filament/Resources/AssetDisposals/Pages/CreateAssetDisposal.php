<?php

namespace App\Filament\Resources\AssetDisposals\Pages;

use App\Filament\Resources\AssetDisposals\AssetDisposalResource;
use App\Models\Asset;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetDisposal extends CreateRecord
{
    protected static string $resource = AssetDisposalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $asset = Asset::findOrFail($data['asset_id']);
        $data['from_status'] = $asset->status;
        $data['to_status'] = $data['transaction_type'] === 'sale' ? 'sold' : 'retired';
        $data['purchase_value'] = $asset->purchase_value;
        $data['accumulated_depreciation'] = $asset->accumulated_depreciation;
        $data['book_value'] = $asset->remaining_value;
        $data['gain_loss'] = round((float) $data['disposal_proceeds'] - (float) $asset->remaining_value, 2);
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->asset->update(['status' => $this->record->to_status]);
    }
}
