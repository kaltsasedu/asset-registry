<?php

namespace App\Filament\Resources\AssetMovements\Pages;

use App\Filament\Resources\AssetMovements\AssetMovementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAssetMovements extends ListRecords
{
    protected static string $resource = AssetMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
