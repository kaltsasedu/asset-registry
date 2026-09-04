<?php

namespace App\Filament\Resources\InventoryCycles\Pages;

use App\Filament\Resources\InventoryCycles\InventoryCycleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInventoryCycle extends CreateRecord
{
    protected static string $resource = InventoryCycleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array { $data['created_by'] = auth()->id(); return $data; }
}
