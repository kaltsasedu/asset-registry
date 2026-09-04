<?php

namespace App\Filament\Resources\ProcurementContracts;

use App\Exports\TableExport;
use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\ProcurementContracts\Pages\CreateProcurementContract;
use App\Filament\Resources\ProcurementContracts\Pages\EditProcurementContract;
use App\Filament\Resources\ProcurementContracts\Pages\ListProcurementContracts;
use App\Models\ProcurementContract;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class ProcurementContractResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'procurement-contracts';
    protected static ?string $model = ProcurementContract::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getNavigationLabel(): string { return 'Συμβάσεις'; }
    public static function getModelLabel(): string { return 'Σύμβαση'; }
    public static function getPluralModelLabel(): string { return 'Συμβάσεις'; }
    public static function getNavigationGroup(): ?string { return 'Μητρώα'; }
    public static function getNavigationSort(): ?int { return 5; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Βασικά στοιχεία σύμβασης')->columns(2)->schema([
                TextInput::make('contract_number')->label('Αριθμός σύμβασης')->required()->maxLength(150)->unique(ignoreRecord: true),
                TextInput::make('title')->label('Τίτλος / αντικείμενο')->required()->maxLength(255),
                Select::make('supplier_id')->label('Ανάδοχος / προμηθευτής')->relationship('supplier', 'name')->searchable()->preload()->required(),
                Select::make('status')->label('Κατάσταση')->options(ProcurementContract::STATUSES)->default('active')->required(),
                DatePicker::make('signed_at')->label('Ημερομηνία υπογραφής'),
                DatePicker::make('starts_at')->label('Έναρξη ισχύος'),
                DatePicker::make('ends_at')->label('Λήξη ισχύος'),
                TextInput::make('total_amount')->label('Συμβατικό ποσό με ΦΠΑ')->numeric()->suffix('€')->required(),
            ]),
            Section::make('Χρηματοδότηση και διοικητικά στοιχεία')->columns(2)->schema([
                Select::make('funding_source_id')->label('Πηγή χρηματοδότησης')->relationship('fundingSource', 'name')->searchable()->preload(),
                Select::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name')->searchable()->preload(),
                TextInput::make('budget_code')->label('Κωδικός προϋπολογισμού / ΚΑΕ'),
                TextInput::make('ada')->label('ΑΔΑ'),
                TextInput::make('kimdis_reference')->label('Αριθμός ΚΗΜΔΗΣ'),
                TextInput::make('decision_number')->label('Απόφαση ανάληψης / κατακύρωσης'),
                FileUpload::make('document_path')->label('Αρχείο υπογεγραμμένης σύμβασης')->disk('local')->directory('procurement-contracts')->preserveFilenames()->columnSpanFull(),
                Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('contract_number')->label('Αρ. σύμβασης')->searchable()->sortable(),
            TextColumn::make('title')->label('Αντικείμενο')->searchable()->wrap(),
            TextColumn::make('supplier.name')->label('Προμηθευτής')->searchable(),
            TextColumn::make('fundingSource.name')->label('Χρηματοδότηση')->toggleable(),
            TextColumn::make('costCenter.name')->label('Κέντρο κόστους')->toggleable(),
            TextColumn::make('total_amount')->label('Συμβατικό ποσό')->money('EUR')->sortable(),
            TextColumn::make('capitalized_receipts_total')->label('Κεφαλαιοποιημένες παραλαβές')->money('EUR'),
            TextColumn::make('remaining_amount')->label('Υπόλοιπο')->money('EUR')->color(fn ($state): string => (float) $state < 0 ? 'danger' : 'success'),
            TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn (string $state): string => ProcurementContract::STATUSES[$state] ?? $state),
        ])->filters([
            SelectFilter::make('status')->label('Κατάσταση')->options(ProcurementContract::STATUSES),
            SelectFilter::make('funding_source_id')->label('Πηγή χρηματοδότησης')->relationship('fundingSource', 'name'),
            SelectFilter::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name'),
        ])->recordActions([
            Action::make('download')->label('Αρχείο')->icon(Heroicon::OutlinedArrowDownTray)->visible(fn (ProcurementContract $record): bool => filled($record->document_path))->url(fn (ProcurementContract $record): string => route('procurement-contracts.download', $record))->openUrlInNewTab(),
            EditAction::make()->label('Επεξεργασία'),
        ])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->visible(fn (): bool => static::canPerform('export'))->action(fn () => Excel::download(new TableExport(
                ['Αρ. σύμβασης', 'Αντικείμενο', 'Προμηθευτής', 'Πηγή χρηματοδότησης', 'Κέντρο κόστους', 'Συμβατικό ποσό', 'Κεφαλαιοποιημένες παραλαβές', 'Υπόλοιπο', 'ΚΑΕ', 'ΑΔΑ', 'ΚΗΜΔΗΣ', 'Κατάσταση'],
                ProcurementContract::with(['supplier', 'fundingSource', 'costCenter'])->orderBy('contract_number')->get()->map(fn (ProcurementContract $contract): array => [$contract->contract_number, $contract->title, $contract->supplier?->name, $contract->fundingSource?->name, $contract->costCenter?->name, $contract->total_amount, $contract->capitalized_receipts_total, $contract->remaining_amount, $contract->budget_code, $contract->ada, $contract->kimdis_reference, ProcurementContract::STATUSES[$contract->status] ?? $contract->status])->all(),
                'Συμβάσεις'
            ), 'symvaseis.xlsx')),
        ])->defaultSort('signed_at', 'desc');
    }

    public static function getPages(): array { return ['index' => ListProcurementContracts::route('/'), 'create' => CreateProcurementContract::route('/create'), 'edit' => EditProcurementContract::route('/{record}/edit')]; }
}
