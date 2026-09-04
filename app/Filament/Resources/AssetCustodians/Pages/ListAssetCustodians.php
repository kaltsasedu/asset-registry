<?php
namespace App\Filament\Resources\AssetCustodians\Pages; use App\Filament\Resources\AssetCustodians\AssetCustodianResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords; class ListAssetCustodians extends ListRecords {protected static string $resource=AssetCustodianResource::class; protected function getHeaderActions():array{return [CreateAction::make()];}}
