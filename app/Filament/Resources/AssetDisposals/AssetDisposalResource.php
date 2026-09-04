<?php

namespace App\Filament\Resources\AssetDisposals;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetDisposals\Pages\CreateAssetDisposal;
use App\Filament\Resources\AssetDisposals\Pages\ListAssetDisposals;
use App\Models\Asset;
use App\Models\AssetStatusTransaction;
use App\Exports\TableExport;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;

class AssetDisposalResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-disposals';
    protected static ?string $model = AssetStatusTransaction::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function getNavigationLabel(): string { return 'Διαγραφές & Μεταβιβάσεις'; }
    public static function getModelLabel(): string { return 'Διαγραφή ή Μεταβίβαση'; }
    public static function getPluralModelLabel(): string { return 'Διαγραφές & Μεταβιβάσεις'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 4; }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('transaction_type', array_keys(AssetStatusTransaction::DISPOSAL_TYPES));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Στοιχεία διαγραφής ή μεταβίβασης')->columns(2)->schema([
                Select::make('asset_id')->label('Πάγιο')
                    ->options(fn (): array => Asset::query()->whereNotIn('status', ['retired', 'sold'])->orderBy('asset_code')->get()
                        ->mapWithKeys(fn (Asset $asset): array => [$asset->id => "{$asset->asset_code} — {$asset->name}"])->all())
                    ->searchable()->preload()->required()->live()
                    ->afterStateUpdated(function ($state, Set $set): void {
                        $asset = Asset::find($state);
                        if (! $asset) return;
                        $set('purchase_value', $asset->purchase_value);
                        $set('accumulated_depreciation', $asset->accumulated_depreciation);
                        $set('book_value', $asset->remaining_value);
                    }),
                Select::make('transaction_type')->label('Ενέργεια')->options(AssetStatusTransaction::DISPOSAL_TYPES)->required(),
                Textarea::make('reason')->label('Αιτία / απόφαση')->required()->columnSpanFull()->maxLength(2000),
                TextInput::make('document_number')->label('Αριθμός πρωτοκόλλου ή εγγράφου')->required()->maxLength(150),
                DatePicker::make('document_date')->label('Ημερομηνία πρωτοκόλλου')->required()->default(now()),
                FileUpload::make('document_path')->label('Σχετικό πρωτόκολλο / έγγραφο')->disk('local')->directory('asset-disposals')->preserveFilenames()->required()->columnSpanFull(),
            ]),
            Section::make('Οικονομικά στοιχεία κατά την πράξη')->description('Οι αξίες λαμβάνονται από το πάγιο και αποθηκεύονται ως ιστορικό στιγμιότυπο.')->columns(2)->schema([
                TextInput::make('purchase_value')->label('Αξία κτήσης')->numeric()->suffix('€')->disabled()->dehydrated()->required(),
                TextInput::make('accumulated_depreciation')->label('Σωρευμένες αποσβέσεις')->numeric()->suffix('€')->disabled()->dehydrated()->required(),
                TextInput::make('book_value')->label('Αναπόσβεστη αξία')->numeric()->suffix('€')->disabled()->dehydrated()->required(),
                TextInput::make('disposal_proceeds')->label('Έσοδο από πώληση')->numeric()->suffix('€')->default(0)->required(),
                Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull()->maxLength(2000),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset.asset_code')->label('Κωδικός')->searchable()->sortable(),
            TextColumn::make('asset.name')->label('Πάγιο')->searchable(),
            TextColumn::make('transaction_type')->label('Ενέργεια')->badge()->formatStateUsing(fn (string $state): string => AssetStatusTransaction::DISPOSAL_TYPES[$state]),
            TextColumn::make('document_number')->label('Πρωτόκολλο')->searchable(),
            TextColumn::make('document_date')->label('Ημερομηνία')->date('d/m/Y')->sortable(),
            TextColumn::make('book_value')->label('Αναπόσβεστη αξία')->money('EUR'),
            TextColumn::make('disposal_proceeds')->label('Έσοδο')->money('EUR'),
            TextColumn::make('gain_loss')->label('Κέρδος / Ζημία')->money('EUR'),
        ])->filters([
            SelectFilter::make('transaction_type')->label('Ενέργεια')->options(AssetStatusTransaction::DISPOSAL_TYPES),
        ])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->action(fn () => Excel::download(new TableExport(['Κωδικός', 'Πάγιο', 'Ενέργεια', 'Πρωτόκολλο', 'Ημερομηνία', 'Αξία κτήσης', 'Σωρευμένες αποσβέσεις', 'Αναπόσβεστη αξία', 'Έσοδο', 'Κέρδος / Ζημία'], AssetStatusTransaction::with('asset')->whereIn('transaction_type', array_keys(AssetStatusTransaction::DISPOSAL_TYPES))->orderByDesc('id')->get()->map(fn ($r) => [$r->asset?->asset_code, $r->asset?->name, AssetStatusTransaction::DISPOSAL_TYPES[$r->transaction_type] ?? $r->transaction_type, $r->document_number, $r->document_date?->format('d/m/Y'), $r->purchase_value, $r->accumulated_depreciation, $r->book_value, $r->disposal_proceeds, $r->gain_loss])->all()), 'diagrafes-metavivaseis.xlsx')),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAssetDisposals::route('/'), 'create' => CreateAssetDisposal::route('/create')];
    }
}
