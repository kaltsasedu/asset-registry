<?php

namespace App\Filament\Resources\DepreciationBooks;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\DepreciationBooks\Pages\CreateDepreciationBook;
use App\Filament\Resources\DepreciationBooks\Pages\EditDepreciationBook;
use App\Filament\Resources\DepreciationBooks\Pages\ListDepreciationBooks;
use App\Models\DepreciationBook;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepreciationBookResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'depreciation-books';
    protected static ?string $model = DepreciationBook::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    public static function getNavigationLabel(): string { return 'Βιβλία Αποσβέσεων'; }
    public static function getModelLabel(): string { return 'Βιβλίο Αποσβέσεων'; }
    public static function getPluralModelLabel(): string { return 'Βιβλία Αποσβέσεων'; }
    public static function getNavigationGroup(): ?string { return 'Ρυθμίσεις'; }
    public static function getNavigationSort(): ?int { return 4; }
    public static function form(Schema $schema): Schema { return $schema->components([
        TextInput::make('name')->label('Ονομασία')->required()->maxLength(100),
        TextInput::make('code')->label('Κωδικός')->required()->maxLength(50)->unique(ignoreRecord: true),
        Textarea::make('description')->label('Περιγραφή')->columnSpanFull(),
        Checkbox::make('is_default')->label('Προεπιλεγμένο βιβλίο'),
        Checkbox::make('is_active')->label('Ενεργό')->default(true),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('name')->label('Βιβλίο')->searchable()->sortable(), TextColumn::make('code')->label('Κωδικός')->searchable(),
        IconColumn::make('is_default')->label('Προεπιλογή')->boolean(), IconColumn::make('is_active')->label('Ενεργό')->boolean(),
    ])->recordActions([EditAction::make()]); }
    public static function getPages(): array { return ['index' => ListDepreciationBooks::route('/'), 'create' => CreateDepreciationBook::route('/create'), 'edit' => EditDepreciationBook::route('/{record}/edit')]; }
}
