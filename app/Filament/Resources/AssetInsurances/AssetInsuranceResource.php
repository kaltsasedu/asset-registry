<?php

namespace App\Filament\Resources\AssetInsurances;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetInsurances\Pages\CreateAssetInsurance;
use App\Filament\Resources\AssetInsurances\Pages\EditAssetInsurance;
use App\Filament\Resources\AssetInsurances\Pages\ListAssetInsurances;
use App\Models\Asset;
use App\Models\AssetInsurance;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetInsuranceResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-insurances';
    protected static ?string $model = AssetInsurance::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function getNavigationLabel(): string { return 'Ασφαλίσεις Παγίων'; }
    public static function getModelLabel(): string { return 'Ασφαλιστήριο Παγίου'; }
    public static function getPluralModelLabel(): string { return 'Ασφαλίσεις Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 8; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asset_id')->label('Πάγιο')->options(fn () => Asset::orderBy('asset_code')->get()->mapWithKeys(fn ($asset) => [$asset->id => $asset->asset_code . ' — ' . $asset->name]))->searchable()->preload()->required(),
            TextInput::make('company')->label('Ασφαλιστική εταιρεία')->required(),
            TextInput::make('policy_number')->label('Αριθμός συμβολαίου')->required(),
            TextInput::make('insured_value')->label('Ασφαλιζόμενη αξία')->numeric()->suffix('€')->required(),
            DatePicker::make('starts_at')->label('Έναρξη')->required(),
            DatePicker::make('expires_at')->label('Λήξη')->required(),
            FileUpload::make('document_path')->label('Ασφαλιστήριο')->disk('local')->directory('asset-insurances')->preserveFilenames(),
            Textarea::make('terms')->label('Όροι / παρατηρήσεις')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset.asset_code')->label('Κωδικός')->searchable(),
            TextColumn::make('asset.name')->label('Πάγιο')->searchable(),
            TextColumn::make('company')->label('Εταιρεία')->searchable(),
            TextColumn::make('policy_number')->label('Συμβόλαιο')->searchable(),
            TextColumn::make('insured_value')->label('Ασφαλιζόμενη αξία')->money('EUR'),
            TextColumn::make('expires_at')->label('Λήξη')->date('d/m/Y')->sortable(),
        ])->recordActions([
            Action::make('download_document')->label('Ασφαλιστήριο')->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (AssetInsurance $record): bool => filled($record->document_path))
                ->url(fn (AssetInsurance $record): string => route('asset-insurances.download', $record))->openUrlInNewTab(),
            EditAction::make(),
        ])->defaultSort('expires_at');
    }

    public static function getPages(): array
    {
        return ['index' => ListAssetInsurances::route('/'), 'create' => CreateAssetInsurance::route('/create'), 'edit' => EditAssetInsurance::route('/{record}/edit')];
    }
}
