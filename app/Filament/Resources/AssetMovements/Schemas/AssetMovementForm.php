<?php

namespace App\Filament\Resources\AssetMovements\Schemas;

use App\Models\Asset;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class AssetMovementForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Αίτημα διακίνησης')->columns(2)->schema([
                Select::make('asset_id')->label('Πάγιο')->relationship('asset', 'name')->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $asset = Asset::find($state);
                        if (! $asset) return;
                        $set('from_location_id', $asset->location_id);
                        $set('from_department_id', $asset->department_id);
                        $set('from_custodian_id', $asset->asset_custodian_id);
                    }),
                DatePicker::make('movement_date')->label('Ημερομηνία αιτήματος')->required()->default(now()),
                Textarea::make('reason')->label('Αιτία μετακίνησης')->required()->maxLength(2000)->columnSpanFull(),
                Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000)->columnSpanFull(),
            ]),
            Section::make('Παράδοση από')->columns(3)->schema([
                Select::make('from_location_id')->label('Τοποθεσία')->relationship('fromLocation', 'name')->searchable()->preload(),
                Select::make('from_department_id')->label('Τμήμα')->relationship('fromDepartment', 'name')->searchable()->preload(),
                Select::make('from_custodian_id')->label('Υπεύθυνος παγίου')->relationship('fromCustodian', 'name')->searchable()->preload(),
                TextInput::make('delivered_by_name')->label('Παραδίδων / υπογράφων')->maxLength(150),
                DatePicker::make('delivered_at')->label('Ημερομηνία παράδοσης'),
            ]),
            Section::make('Παραλαβή από')->columns(3)->schema([
                Select::make('to_location_id')->label('Τοποθεσία')->relationship('toLocation', 'name')->searchable()->preload()->required(),
                Select::make('to_department_id')->label('Τμήμα')->relationship('toDepartment', 'name')->searchable()->preload(),
                Select::make('to_custodian_id')->label('Υπεύθυνος παγίου')->relationship('toCustodian', 'name')->searchable()->preload(),
                TextInput::make('received_by_name')->label('Παραλαμβάνων / υπογράφων')->maxLength(150),
                DatePicker::make('received_at')->label('Ημερομηνία παραλαβής'),
            ]),
        ]);
    }
}
