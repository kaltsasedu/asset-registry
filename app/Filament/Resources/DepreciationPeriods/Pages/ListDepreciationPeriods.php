<?php
namespace App\Filament\Resources\DepreciationPeriods\Pages;
use App\Filament\Resources\DepreciationPeriods\DepreciationPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListDepreciationPeriods extends ListRecords { protected static string $resource = DepreciationPeriodResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Νέα περίοδος')]; } }
