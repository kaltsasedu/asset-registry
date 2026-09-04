<?php
namespace App\Filament\Resources\ProcurementContracts\Pages;
use App\Filament\Resources\ProcurementContracts\ProcurementContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListProcurementContracts extends ListRecords { protected static string $resource = ProcurementContractResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Νέα σύμβαση')]; } }
