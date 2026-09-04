<?php

namespace App\Filament\Resources\Suppliers;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\Suppliers\Pages\CreateSupplier;
use App\Filament\Resources\Suppliers\Pages\EditSupplier;
use App\Filament\Resources\Suppliers\Pages\ListSuppliers;
use App\Filament\Resources\Suppliers\Schemas\SupplierForm;
use App\Filament\Resources\Suppliers\Tables\SuppliersTable;
use App\Models\Supplier;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'suppliers';
    protected static ?string $model = Supplier::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return SupplierForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SuppliersTable::configure($table);
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
            'index' => ListSuppliers::route('/'),
            'create' => CreateSupplier::route('/create'),
            'edit' => EditSupplier::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return 'Προμηθευτές';
    }

    public static function getModelLabel(): string
    {
        return 'Προμηθευτής';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Προμηθευτές';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Μητρώα';
    }

    public static function getNavigationSort(): ?int { return 1; }
    
}
