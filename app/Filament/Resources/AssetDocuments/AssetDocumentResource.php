<?php

namespace App\Filament\Resources\AssetDocuments;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetDocuments\Pages\CreateAssetDocument;
use App\Filament\Resources\AssetDocuments\Pages\EditAssetDocument;
use App\Filament\Resources\AssetDocuments\Pages\ListAssetDocuments;
use App\Models\Asset;
use App\Models\AssetDocument;
use App\Exports\TableExport;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class AssetDocumentResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-documents';
    protected static ?string $model = AssetDocument::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;

    public static function getNavigationLabel(): string { return 'Δικαιολογητικά Παγίων'; }
    public static function getModelLabel(): string { return 'Δικαιολογητικό Παγίου'; }
    public static function getPluralModelLabel(): string { return 'Δικαιολογητικά Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 5; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asset_id')->label('Πάγιο')->required()->searchable()->preload()
                ->options(fn (): array => Asset::query()->orderBy('asset_code')->get()->mapWithKeys(fn (Asset $asset): array => [$asset->id => "{$asset->asset_code} — {$asset->name}"])->all()),
            Select::make('document_type')->label('Είδος δικαιολογητικού')->options(AssetDocument::TYPES)->required(),
            TextInput::make('title')->label('Τίτλος / περιγραφή')->required()->maxLength(255),
            TextInput::make('document_number')->label('Αριθμός εγγράφου ή πρωτοκόλλου')->maxLength(150),
            DatePicker::make('document_date')->label('Ημερομηνία εγγράφου'),
            FileUpload::make('file_path')->label('Αρχείο')->disk('local')->directory('asset-documents')->preserveFilenames()->required()->columnSpanFull(),
            Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset.asset_code')->label('Κωδικός παγίου')->searchable()->sortable(),
            TextColumn::make('asset.name')->label('Πάγιο')->searchable(),
            TextColumn::make('document_type')->label('Είδος')->badge()->formatStateUsing(fn (string $state): string => AssetDocument::TYPES[$state] ?? $state),
            TextColumn::make('title')->label('Τίτλος')->searchable(),
            TextColumn::make('document_number')->label('Αρ. εγγράφου')->toggleable(),
            TextColumn::make('document_date')->label('Ημερομηνία')->date('d/m/Y')->sortable()->toggleable(),
            TextColumn::make('file_path')->label('Αρχείο')->formatStateUsing(fn (): string => 'Άνοιγμα')
                ->url(fn (AssetDocument $record): string => route('asset-documents.download', $record))->openUrlInNewTab(),
        ])->filters([
            SelectFilter::make('asset_id')->label('Πάγιο')->relationship('asset', 'asset_code'),
            SelectFilter::make('document_type')->label('Είδος')->options(AssetDocument::TYPES),
        ])->recordActions([EditAction::make()->label('Επεξεργασία')])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->action(fn () => Excel::download(new TableExport(['Κωδικός παγίου', 'Πάγιο', 'Είδος', 'Τίτλος', 'Αρ. εγγράφου', 'Ημερομηνία', 'Παρατηρήσεις'], AssetDocument::with('asset')->orderByDesc('id')->get()->map(fn ($r) => [$r->asset?->asset_code, $r->asset?->name, AssetDocument::TYPES[$r->document_type] ?? $r->document_type, $r->title, $r->document_number, $r->document_date?->format('d/m/Y'), $r->notes])->all()), 'dikaiologitika-pagion.xlsx')),
        ])->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAssetDocuments::route('/'), 'create' => CreateAssetDocument::route('/create'), 'edit' => EditAssetDocument::route('/{record}/edit')];
    }
}
