<?php
namespace App\Filament\Resources\AssetDocuments\Pages;
use App\Filament\Resources\AssetDocuments\AssetDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListAssetDocuments extends ListRecords { protected static string $resource = AssetDocumentResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Νέο δικαιολογητικό')]; } }
