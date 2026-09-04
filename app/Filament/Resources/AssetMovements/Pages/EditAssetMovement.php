<?php

namespace App\Filament\Resources\AssetMovements\Pages;

use App\Filament\Resources\AssetMovements\AssetMovementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAssetMovement extends EditRecord
{
    protected static string $resource = AssetMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
