<?php
namespace App\Filament\Resources\AssetDocuments\Pages;
use App\Filament\Resources\AssetDocuments\AssetDocumentResource;
use Filament\Resources\Pages\CreateRecord;
class CreateAssetDocument extends CreateRecord { protected static string $resource = AssetDocumentResource::class; protected function mutateFormDataBeforeCreate(array $data): array { $data['created_by'] = auth()->id(); return $data; } }
