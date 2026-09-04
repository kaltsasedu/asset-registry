<?php

namespace App\Filament\Resources\CostCenters;

use App\Exports\TableExport;
use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\CostCenters\Pages\CreateCostCenter;
use App\Filament\Resources\CostCenters\Pages\EditCostCenter;
use App\Filament\Resources\CostCenters\Pages\ListCostCenters;
use App\Models\CostCenter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class CostCenterResource extends Resource
{
    use HasPermissionChecks;
    protected static string $permissionPrefix = 'cost-centers';
    protected static ?string $model = CostCenter::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    public static function getNavigationLabel(): string { return 'Κέντρα Κόστους'; }
    public static function getModelLabel(): string { return 'Κέντρο Κόστους'; }
    public static function getPluralModelLabel(): string { return 'Κέντρα Κόστους'; }
    public static function getNavigationGroup(): ?string { return 'Μητρώα'; }
    public static function getNavigationSort(): ?int { return 7; }
    public static function form(Schema $schema): Schema { return $schema->components([
        TextInput::make('code')->label('Κωδικός')->required()->maxLength(80)->unique(ignoreRecord: true),
        TextInput::make('name')->label('Ονομασία')->required()->maxLength(255),
        Select::make('department_id')->label('Συνδεδεμένο τμήμα')->relationship('department', 'name')->searchable()->preload(),
        Checkbox::make('is_active')->label('Ενεργό')->default(true),
        Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull(),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('code')->label('Κωδικός')->searchable()->sortable(), TextColumn::make('name')->label('Ονομασία')->searchable()->sortable(),
        TextColumn::make('department.name')->label('Τμήμα')->toggleable(), IconColumn::make('is_active')->label('Ενεργό')->boolean(),
    ])->recordActions([EditAction::make()])->toolbarActions([Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->visible(fn (): bool => static::canPerform('export'))->action(fn () => Excel::download(new TableExport(
        ['Κωδικός', 'Ονομασία', 'Τμήμα', 'Ενεργό'], CostCenter::with('department')->orderBy('code')->get()->map(fn ($row) => [$row->code, $row->name, $row->department?->name, $row->is_active ? 'Ναι' : 'Όχι'])->all(), 'Κέντρα Κόστους'
    ), 'kentra-kostous.xlsx'))]); }
    public static function getPages(): array { return ['index' => ListCostCenters::route('/'), 'create' => CreateCostCenter::route('/create'), 'edit' => EditCostCenter::route('/{record}/edit')]; }
}
