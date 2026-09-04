<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Exports\TableExport;
use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use App\Models\AssetDocument;
use App\Models\AssetInsurance;
use App\Models\AssetMaintenance;
use App\Models\AssetMovement;
use App\Models\AssetStatusTransaction;
use App\Models\DepreciationEntry;
use App\Models\InventoryEntry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'activity-logs';
    protected static ?string $model = Activity::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    public static function getNavigationLabel(): string { return 'Ιστορικό Αλλαγών'; }
    public static function getPluralModelLabel(): string { return 'Ιστορικό Αλλαγών'; }
    public static function getNavigationGroup(): ?string { return 'Ρυθμίσεις'; }
    public static function getNavigationSort(): ?int { return 3; }
    public static function form(Schema $schema): Schema { return $schema->components([]); }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user || $user->hasFullDataAccess()) {
            return $query;
        }

        $models = self::assetSubjectModels();

        return $query->where(function (Builder $activity) use ($models): void {
            $activity->whereNotIn('subject_type', array_keys($models));

            foreach ($models as $model => $modelQuery) {
                $activity->orWhere(function (Builder $subject) use ($model, $modelQuery): void {
                    $subject->where('subject_type', $model)
                        ->whereIn('subject_id', $modelQuery()->select('id'));
                });
            }
        });
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('created_at')->label('Ημερομηνία')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('causer.name')->label('Χρήστης')->default('Σύστημα')->searchable(),
            TextColumn::make('log_name')->label('Ενότητα')->toggleable(),
            TextColumn::make('event')->label('Ενέργεια')->badge()->formatStateUsing(fn (?string $state): string => self::eventLabels()[$state] ?? $state ?? '—'),
            TextColumn::make('description')->label('Περιγραφή')->wrap(),
            TextColumn::make('subject.asset_code')->label('Κωδικός Παγίου')->toggleable(),
        ])->filters([
            SelectFilter::make('event')->label('Ενέργεια')->options(self::eventLabels()),
        ])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)
                ->action(fn () => Excel::download(new TableExport(
                    ['Ημερομηνία', 'Χρήστης', 'Ενότητα', 'Ενέργεια', 'Περιγραφή'],
                    self::getEloquentQuery()->with('causer')->orderByDesc('created_at')->get()->map(fn ($record) => [
                        $record->created_at?->format('d/m/Y H:i'),
                        $record->causer?->name ?? 'Σύστημα',
                        $record->log_name,
                        self::eventLabels()[$record->event] ?? $record->event,
                        $record->description,
                    ])->all(),
                ), 'istoriko-allagon.xlsx')),
        ])->defaultSort('created_at', 'desc');
    }

    /** @return array<class-string, callable(): Builder> */
    private static function assetSubjectModels(): array
    {
        return [
            Asset::class => fn (): Builder => Asset::query(),
            AssetMovement::class => fn (): Builder => AssetMovement::query(),
            AssetDocument::class => fn (): Builder => AssetDocument::query(),
            AssetStatusTransaction::class => fn (): Builder => AssetStatusTransaction::query(),
            AssetMaintenance::class => fn (): Builder => AssetMaintenance::query(),
            AssetInsurance::class => fn (): Builder => AssetInsurance::query(),
            InventoryEntry::class => fn (): Builder => InventoryEntry::query(),
            DepreciationEntry::class => fn (): Builder => DepreciationEntry::query(),
            AssetDepreciationProfile::class => fn (): Builder => AssetDepreciationProfile::query(),
        ];
    }

    private static function eventLabels(): array
    {
        return ['created' => 'Δημιουργία', 'updated' => 'Ενημέρωση', 'deleted' => 'Διαγραφή', 'imported' => 'Εισαγωγή', 'exported' => 'Εξαγωγή', 'restored' => 'Επαναφορά'];
    }

    public static function getPages(): array
    {
        return ['index' => ListActivityLogs::route('/')];
    }
}
