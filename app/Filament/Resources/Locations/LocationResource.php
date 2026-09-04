<?php

namespace App\Filament\Resources\Locations;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Locations\Pages\CreateLocation;
use App\Filament\Resources\Locations\Pages\EditLocation;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Filament\Resources\Locations\Schemas\LocationForm;
use App\Filament\Resources\Locations\Tables\LocationsTable;
use App\Models\Location;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class LocationResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'locations';
    protected static ?string $model = Location::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return LocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LocationsTable::configure($table);
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
            'index' => ListLocations::route('/'),
            'create' => CreateLocation::route('/create'),
            'edit' => EditLocation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Τοποθεσίες';
    }

    public static function getModelLabel(): string
    {
        return 'Τοποθεσία';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Τοποθεσίες';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Μητρώα';
    }

    public static function getNavigationSort(): ?int { return 3; }
}
