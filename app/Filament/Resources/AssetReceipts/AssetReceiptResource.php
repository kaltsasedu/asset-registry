<?php

namespace App\Filament\Resources\AssetReceipts;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetReceipts\Pages\CreateAssetReceipt;
use App\Filament\Resources\AssetReceipts\Pages\EditAssetReceipt;
use App\Filament\Resources\AssetReceipts\Pages\ListAssetReceipts;
use App\Filament\Resources\AssetReceipts\RelationManagers\AssetReceiptLinesRelationManager;
use App\Exports\TableExport;
use App\Models\AssetReceipt;
use App\Models\ProcurementContract;
use App\Services\AssetReceiptCapitalizationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class AssetReceiptResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-receipts';
    protected static ?string $model = AssetReceipt::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    public static function getNavigationLabel(): string { return 'Παραλαβές & Κεφαλαιοποίηση'; }
    public static function getModelLabel(): string { return 'Παραλαβή Παγίων'; }
    public static function getPluralModelLabel(): string { return 'Παραλαβές Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 2; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Στοιχεία παραλαβής')->columns(2)->schema([
                TextInput::make('receipt_number')->label('Αριθμός παραλαβής')->disabled()->dehydrated()->helperText('Δημιουργείται αυτόματα.'),
                DatePicker::make('receipt_date')->label('Ημερομηνία παραλαβής')->default(now())->required(),
                Select::make('supplier_id')->label('Προμηθευτής')->relationship('supplier', 'name')->searchable()->preload()->required(),
                Select::make('funding_source_id')->label('Πηγή χρηματοδότησης')->relationship('fundingSource', 'name')->searchable()->preload(),
                Select::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name')->searchable()->preload(),
                Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull()->maxLength(2000),
            ]),
            Section::make('Σύμβαση, τιμολόγιο και πρωτόκολλο')->columns(2)->schema([
                Select::make('procurement_contract_id')->label('Συνδεδεμένη σύμβαση')
                    ->options(fn (): array => ProcurementContract::query()->whereIn('status', ['active', 'completed'])->orderBy('contract_number')->get()->mapWithKeys(fn (ProcurementContract $contract): array => [$contract->id => $contract->contract_number . ' — ' . $contract->title])->all())
                    ->searchable()->preload()->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $contract = ProcurementContract::find($state);
                        if (! $contract) return;
                        $set('supplier_id', $contract->supplier_id);
                        $set('funding_source_id', $contract->funding_source_id);
                        $set('cost_center_id', $contract->cost_center_id);
                        $set('contract_number', $contract->contract_number);
                        $set('contract_date', $contract->signed_at?->toDateString());
                    }),
                TextInput::make('contract_number')->label('Αριθμός σύμβασης')->maxLength(150),
                DatePicker::make('contract_date')->label('Ημερομηνία σύμβασης'),
                FileUpload::make('contract_file_path')->label('Αρχείο σύμβασης')->disk('local')->directory('asset-receipts')->preserveFilenames(),
                TextInput::make('invoice_number')->label('Αριθμός τιμολογίου')->maxLength(150),
                DatePicker::make('invoice_date')->label('Ημερομηνία τιμολογίου'),
                FileUpload::make('invoice_file_path')->label('Αρχείο τιμολογίου')->disk('local')->directory('asset-receipts')->preserveFilenames(),
                TextInput::make('acceptance_protocol_number')->label('Αριθμός πρωτοκόλλου παραλαβής')->maxLength(150),
                DatePicker::make('acceptance_protocol_date')->label('Ημερομηνία πρωτοκόλλου'),
                FileUpload::make('acceptance_protocol_file_path')->label('Αρχείο πρωτοκόλλου')->disk('local')->directory('asset-receipts')->preserveFilenames(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('receipt_number')->label('Αρ. παραλαβής')->searchable()->sortable(),
            TextColumn::make('receipt_date')->label('Ημερομηνία')->date('d/m/Y')->sortable(),
            TextColumn::make('supplier.name')->label('Προμηθευτής')->searchable(),
            TextColumn::make('procurementContract.contract_number')->label('Σύμβαση')->searchable()->toggleable(),
            TextColumn::make('fundingSource.name')->label('Χρηματοδότηση')->toggleable(),
            TextColumn::make('costCenter.name')->label('Κέντρο κόστους')->toggleable(),
            TextColumn::make('total_amount')->label('Αξία παραλαβής')->money('EUR'),
            TextColumn::make('invoice_number')->label('Τιμολόγιο')->searchable()->toggleable(),
            TextColumn::make('acceptance_protocol_number')->label('Πρωτόκολλο')->toggleable(),
            TextColumn::make('lines_count')->counts('lines')->label('Γραμμές'),
            TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn (string $state): string => AssetReceipt::STATUSES[$state]),
            TextColumn::make('capitalized_at')->label('Κεφαλαιοποίηση')->dateTime('d/m/Y H:i')->toggleable(),
        ])->filters([
            SelectFilter::make('status')->label('Κατάσταση')->options(AssetReceipt::STATUSES),
            SelectFilter::make('supplier_id')->label('Προμηθευτής')->relationship('supplier', 'name'),
            SelectFilter::make('funding_source_id')->label('Χρηματοδότηση')->relationship('fundingSource', 'name'),
            SelectFilter::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name'),
        ])->recordActions([
            Action::make('capitalize')->label('Κεφαλαιοποίηση σε πάγια')->icon(Heroicon::OutlinedBanknotes)
                ->visible(fn (AssetReceipt $record): bool => $record->status === 'draft' && static::canPerform('capitalize'))
                ->requiresConfirmation()->modalHeading('Κεφαλαιοποίηση παραλαβής')->modalDescription('Θα δημιουργηθούν πάγια για κάθε ποσότητα των γραμμών. Η ενέργεια δεν αναιρείται από αυτή την οθόνη.')
                ->action(function (AssetReceipt $record): void {
                    $count = app(AssetReceiptCapitalizationService::class)->capitalize($record);
                    Notification::make()->success()->title('Η κεφαλαιοποίηση ολοκληρώθηκε')->body("Δημιουργήθηκαν {$count} πάγια με τα συνδεδεμένα δικαιολογητικά τους.")->send();
                }),
            EditAction::make()->label('Άνοιγμα'),
        ])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->visible(fn (): bool => static::canPerform('export'))->action(fn () => Excel::download(new TableExport(
                ['Αρ. παραλαβής', 'Ημερομηνία', 'Προμηθευτής', 'Σύμβαση', 'Χρηματοδότηση', 'Κέντρο κόστους', 'Αξία', 'Τιμολόγιο', 'Πρωτόκολλο', 'Γραμμές', 'Κατάσταση'],
                AssetReceipt::query()->with(['supplier', 'procurementContract', 'fundingSource', 'costCenter'])->withCount('lines')->orderByDesc('receipt_date')->get()->map(fn (AssetReceipt $receipt): array => [$receipt->receipt_number, $receipt->receipt_date?->format('d/m/Y'), $receipt->supplier?->name, $receipt->procurementContract?->contract_number, $receipt->fundingSource?->name, $receipt->costCenter?->name, $receipt->total_amount, $receipt->invoice_number, $receipt->acceptance_protocol_number, $receipt->lines_count, AssetReceipt::STATUSES[$receipt->status]])->all(),
                'Παραλαβές Παγίων'
            ), 'paralaves-pagion.xlsx')),
        ])->defaultSort('receipt_date', 'desc');
    }

    public static function getRelations(): array { return [AssetReceiptLinesRelationManager::class]; }
    public static function getPages(): array { return ['index' => ListAssetReceipts::route('/'), 'create' => CreateAssetReceipt::route('/create'), 'edit' => EditAssetReceipt::route('/{record}/edit')]; }
}
