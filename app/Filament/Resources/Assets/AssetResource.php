<?php

namespace App\Filament\Resources\Assets;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Assets\Pages\CreateAsset;
use App\Filament\Resources\Assets\Pages\EditAsset;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Filament\Resources\Assets\Schemas\AssetForm;
use App\Filament\Resources\Assets\Tables\AssetsTable;
use App\Filament\Resources\Assets\RelationManagers\AssetDocumentsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\AssetDisposalsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\AssetMovementsRelationManager;
use App\Filament\Resources\Assets\RelationManagers\AssetDepreciationProfilesRelationManager;
use App\Filament\Resources\Assets\RelationManagers\AssetMaintenancesRelationManager;
use App\Filament\Resources\Assets\RelationManagers\AssetInsurancesRelationManager;
use App\Models\Asset;
use App\Services\AssetDataScope;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'assets';
    protected static ?string $model = Asset::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AssetForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return static::canPerform('create') && app(AssetDataScope::class)->canCreateAssets(auth()->user());
    }

    public static function getRelations(): array
    {
        return [
            AssetMovementsRelationManager::class,
            AssetDisposalsRelationManager::class,
            AssetDocumentsRelationManager::class,
            AssetDepreciationProfilesRelationManager::class,
            AssetMaintenancesRelationManager::class,
            AssetInsurancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssets::route('/'),
            'create' => CreateAsset::route('/create'),
            'edit' => EditAsset::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Πάγια';
    }

    public static function getModelLabel(): string
    {
        return 'Πάγιο';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Πάγια';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Παρακολούθηση';
    }   

    public static function getNavigationSort(): ?int { return 1; }
}
