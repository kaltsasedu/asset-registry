<?php

namespace App\Filament\Resources\AssetMovements;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetMovements\Pages\CreateAssetMovement;
use App\Filament\Resources\AssetMovements\Pages\EditAssetMovement;
use App\Filament\Resources\AssetMovements\Pages\ListAssetMovements;
use App\Filament\Resources\AssetMovements\Schemas\AssetMovementForm;
use App\Filament\Resources\AssetMovements\Tables\AssetMovementsTable;
use App\Models\AssetMovement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AssetMovementResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-movements';
    protected static ?string $model = AssetMovement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return AssetMovementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AssetMovementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetMovements::route('/'),
            'create' => CreateAssetMovement::route('/create'),
            'edit' => EditAssetMovement::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Μετακινήσεις Παγίων';
    }

    public static function getModelLabel(): string
    {
        return 'Μετακίνηση Παγίου';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Μετακινήσεις Παγίων';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Παρακολούθηση';
    }

    public static function getNavigationSort(): ?int { return 2; }

}
