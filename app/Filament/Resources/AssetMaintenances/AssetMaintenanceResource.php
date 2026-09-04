<?php

namespace App\Filament\Resources\AssetMaintenances;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetMaintenances\Pages\CreateAssetMaintenance;
use App\Filament\Resources\AssetMaintenances\Pages\EditAssetMaintenance;
use App\Filament\Resources\AssetMaintenances\Pages\ListAssetMaintenances;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AssetMaintenanceResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-maintenances';
    protected static ?string $model = AssetMaintenance::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    public static function getNavigationLabel(): string { return 'Συντηρήσεις Παγίων'; }
    public static function getModelLabel(): string { return 'Συντήρηση Παγίου'; }
    public static function getPluralModelLabel(): string { return 'Συντηρήσεις Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 7; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('asset_id')->label('Πάγιο')->options(fn () => Asset::orderBy('asset_code')->get()->mapWithKeys(fn ($asset) => [$asset->id => $asset->asset_code . ' — ' . $asset->name]))->searchable()->preload()->required(),
            TextInput::make('title')->label('Εργασία')->required(),
            Select::make('maintenance_type')->label('Είδος')->options(AssetMaintenance::TYPES)->required(),
            Select::make('status')->label('Κατάσταση')->options(AssetMaintenance::STATUSES)->default('scheduled')->required(),
            DatePicker::make('scheduled_at')->label('Προγραμματισμένη ημερομηνία'),
            DatePicker::make('performed_at')->label('Ημερομηνία εκτέλεσης'),
            TextInput::make('interval_months')->label('Επανάληψη κάθε')->numeric()->suffix('μήνες'),
            DatePicker::make('next_service_at')->label('Επόμενο service'),
            TextInput::make('technician')->label('Τεχνικός / συνεργείο'),
            Select::make('assigned_to_user_id')->label('Ανάθεση σε χρήστη')->options(fn () => User::orderBy('name')->pluck('name', 'id'))->searchable()->preload(),
            TextInput::make('cost')->label('Κόστος')->numeric()->suffix('€')->default(0),
            TextInput::make('document_number')->label('Αρ. δελτίου / παραστατικού'),
            Textarea::make('description')->label('Περιγραφή')->columnSpanFull(),
            Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('asset.asset_code')->label('Κωδικός')->searchable(),
            TextColumn::make('asset.name')->label('Πάγιο')->searchable(),
            TextColumn::make('title')->label('Εργασία')->searchable(),
            TextColumn::make('maintenance_type')->label('Είδος')->badge()->formatStateUsing(fn ($state) => AssetMaintenance::TYPES[$state]),
            TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn ($state) => AssetMaintenance::STATUSES[$state]),
            TextColumn::make('assignee.name')->label('Ανατέθηκε σε')->toggleable(),
            TextColumn::make('next_service_at')->label('Επόμενο service')->date('d/m/Y')->sortable(),
            TextColumn::make('cost')->label('Κόστος')->money('EUR'),
        ])->filters([
            SelectFilter::make('status')->label('Κατάσταση')->options(AssetMaintenance::STATUSES),
            SelectFilter::make('maintenance_type')->label('Είδος')->options(AssetMaintenance::TYPES),
        ])->recordActions([
            Action::make('work_order')->label('Δελτίο εργασίας')->icon(Heroicon::OutlinedDocumentArrowDown)->url(fn (AssetMaintenance $record): string => route('maintenance.work-order', $record))->openUrlInNewTab(),
            EditAction::make(),
        ])
            ->headerActions([
                Action::make('calendar')->label('Ημερολόγιο συντηρήσεων')->icon(Heroicon::OutlinedCalendarDays)->url('/admin/maintenance-calendar'),
                Action::make('reports')->label('Αναφορά κόστους')->url(route('maintenance.reports')),
            ])
            ->defaultSort('next_service_at');
    }

    public static function getPages(): array
    {
        return ['index' => ListAssetMaintenances::route('/'), 'create' => CreateAssetMaintenance::route('/create'), 'edit' => EditAssetMaintenance::route('/{record}/edit')];
    }
}
