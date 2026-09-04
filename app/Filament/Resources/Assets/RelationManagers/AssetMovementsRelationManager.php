<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Models\Asset;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'movements';
    protected static ?string $title = 'Μετακινήσεις Παγίων';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Αίτημα διακίνησης')->columns(2)->schema([
                DatePicker::make('movement_date')->label('Ημερομηνία αιτήματος')->required()->default(now()),
                Textarea::make('reason')->label('Αιτία μετακίνησης')->required()->maxLength(2000)->columnSpanFull(),
                Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000)->columnSpanFull(),
            ]),
            Section::make('Παράδοση και παραλαβή')->columns(3)->schema([
                Select::make('from_location_id')->label('Από τοποθεσία')->relationship('fromLocation', 'name')->searchable()->preload()->default(fn (): ?int => $this->getOwnerRecord()->location_id),
                Select::make('from_department_id')->label('Από τμήμα')->relationship('fromDepartment', 'name')->searchable()->preload()->default(fn (): ?int => $this->getOwnerRecord()->department_id),
                Select::make('from_custodian_id')->label('Παραδίδων υπεύθυνος')->relationship('fromCustodian', 'name')->searchable()->preload()->default(fn (): ?int => $this->getOwnerRecord()->asset_custodian_id),
                Select::make('to_location_id')->label('Προς τοποθεσία')->relationship('toLocation', 'name')->searchable()->preload()->required(),
                Select::make('to_department_id')->label('Προς τμήμα')->relationship('toDepartment', 'name')->searchable()->preload(),
                Select::make('to_custodian_id')->label('Παραλαμβάνων υπεύθυνος')->relationship('toCustodian', 'name')->searchable()->preload(),
                TextInput::make('delivered_by_name')->label('Παραδίδων / υπογράφων')->maxLength(150),
                TextInput::make('received_by_name')->label('Παραλαμβάνων / υπογράφων')->maxLength(150),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('movement_date')->label('Ημερομηνία')->date('d/m/Y')->sortable(),
            TextColumn::make('fromLocation.name')->label('Από'),
            TextColumn::make('toLocation.name')->label('Προς'),
            TextColumn::make('toCustodian.name')->label('Υπεύθυνος παραλαβής'),
            TextColumn::make('reason')->label('Αιτία')->wrap(),
            TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn (string $state): string => ['draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε'][$state] ?? $state),
        ])->headerActions([
            CreateAction::make()->label('Νέα μετακίνηση'),
        ])->recordActions([
            EditAction::make()->label('Επεξεργασία')->visible(fn ($record): bool => $record->status === 'draft'),
            DeleteAction::make()->visible(fn ($record): bool => $record->status === 'draft'),
        ])->defaultSort('movement_date', 'desc');
    }
}
