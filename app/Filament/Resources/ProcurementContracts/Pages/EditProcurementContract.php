<?php
namespace App\Filament\Resources\ProcurementContracts\Pages;
use App\Filament\Resources\ProcurementContracts\ProcurementContractResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
class EditProcurementContract extends EditRecord { protected static string $resource = ProcurementContractResource::class; protected function getHeaderActions(): array { return [DeleteAction::make()]; } }
