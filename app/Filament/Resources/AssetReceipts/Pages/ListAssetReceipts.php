<?php

namespace App\Filament\Resources\AssetReceipts\Pages;

use App\Filament\Resources\AssetReceipts\AssetReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetReceipts extends ListRecords
{
    protected static string $resource = AssetReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Νέα παραλαβή')];
    }
}
