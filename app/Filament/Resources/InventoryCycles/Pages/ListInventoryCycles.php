<?php

namespace App\Filament\Resources\InventoryCycles\Pages;

use App\Filament\Resources\InventoryCycles\InventoryCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryCycles extends ListRecords
{
    protected static string $resource = InventoryCycleResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()->label('Νέα Απογραφή')]; }
}
