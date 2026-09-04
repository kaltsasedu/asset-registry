<?php

namespace App\Filament\Resources\InventoryCycles;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\InventoryCycles\Pages\CreateInventoryCycle;
use App\Filament\Resources\InventoryCycles\Pages\EditInventoryCycle;
use App\Filament\Resources\InventoryCycles\Pages\ListInventoryCycles;
use App\Models\InventoryCycle;
use App\Exports\TableExport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class InventoryCycleResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'inventory';
    protected static ?string $model = InventoryCycle::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQrCode;

    public static function getNavigationLabel(): string { return 'Φυσικές Απογραφές'; }
    public static function getModelLabel(): string { return 'Φυσική Απογραφή'; }
    public static function getPluralModelLabel(): string { return 'Φυσικές Απογραφές'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 3; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ονομασία απογραφής')->required()->maxLength(150),
            DatePicker::make('starts_at')->label('Ημερομηνία έναρξης')->required()->default(now()),
            DatePicker::make('ends_at')->label('Ημερομηνία λήξης'),
            Select::make('location_id')->label('Περιορισμός τοποθεσίας')->relationship('location', 'name')->searchable()->preload(),
            Select::make('department_id')->label('Περιορισμός τμήματος')->relationship('department', 'name')->searchable()->preload(),
            Select::make('status')->label('Κατάσταση')->options([
                'draft' => 'Πρόχειρη', 'active' => 'Ενεργή', 'completed' => 'Ολοκληρωμένη',
            ])->required()->default('draft'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Απογραφή')->searchable()->sortable(),
                TextColumn::make('starts_at')->label('Έναρξη')->date('d/m/Y')->sortable(),
                TextColumn::make('location.name')->label('Τοποθεσία')->placeholder('Όλες')->toggleable(),
                TextColumn::make('department.name')->label('Τμήμα')->placeholder('Όλα')->toggleable(),
                TextColumn::make('entries_count')->label('Σαρώσεις')->counts('entries')->sortable(),
                TextColumn::make('status')->label('Κατάσταση')->badge()->color(fn (string $state): string|array => match ($state) {
                    'draft' => Color::Gray, 'active' => Color::Blue, 'completed' => Color::Green,
                })->formatStateUsing(fn (string $state): string => match ($state) {
                    'draft' => 'Πρόχειρη', 'active' => 'Ενεργή', 'completed' => 'Ολοκληρωμένη',
                }),
            ])
            ->filters([
                SelectFilter::make('status')->label('Κατάσταση')->options(['draft' => 'Πρόχειρη', 'active' => 'Ενεργή', 'completed' => 'Ολοκληρωμένη']),
            ])
            ->recordActions([
                Action::make('deviations')->label('Αποκλίσεις')->icon(Heroicon::OutlinedExclamationTriangle)->color('warning')
                    ->url(fn (InventoryCycle $record): string => route('inventory.deviations', $record)),
                Action::make('scan')->label('Σάρωση QR')->icon(Heroicon::OutlinedQrCode)->color('primary')
                    ->url(fn (InventoryCycle $record): string => route('inventory.scan', $record))
                    ->visible(fn (InventoryCycle $record): bool => $record->status !== 'completed'),
                EditAction::make(),
            ])
            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->action(fn () => Excel::download(new TableExport(['Απογραφή', 'Έναρξη', 'Λήξη', 'Τοποθεσία', 'Τμήμα', 'Σαρώσεις', 'Κατάσταση'], InventoryCycle::withCount('entries')->with(['location', 'department'])->orderByDesc('starts_at')->get()->map(fn ($r) => [$r->name, $r->starts_at?->format('d/m/Y'), $r->ends_at?->format('d/m/Y'), $r->location?->name, $r->department?->name, $r->entries_count, match ($r->status) {'draft' => 'Πρόχειρη', 'active' => 'Ενεργή', 'completed' => 'Ολοκληρωμένη'}])->all()), 'fysikes-apografes.xlsx')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInventoryCycles::route('/'),
            'create' => CreateInventoryCycle::route('/create'),
            'edit' => EditInventoryCycle::route('/{record}/edit'),
        ];
    }
}
