<?php

namespace App\Filament\Resources\DepreciationPeriods;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\DepreciationPeriods\Pages\CreateDepreciationPeriod;
use App\Filament\Resources\DepreciationPeriods\Pages\EditDepreciationPeriod;
use App\Filament\Resources\DepreciationPeriods\Pages\ListDepreciationPeriods;
use App\Models\DepreciationPeriod;
use App\Services\DepreciationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepreciationPeriodResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'depreciation-periods';
    protected static ?string $model = DepreciationPeriod::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;
    public static function getNavigationLabel(): string { return 'Αποσβέσεις'; }
    public static function getModelLabel(): string { return 'Περίοδος Απόσβεσης'; }
    public static function getPluralModelLabel(): string { return 'Αποσβέσεις'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 5; }
    public static function form(Schema $schema): Schema { return $schema->components([
        Select::make('depreciation_book_id')->label('Βιβλίο αποσβέσεων')->relationship('book', 'name')->searchable()->preload()->required(),
        TextInput::make('year')->label('Έτος')->integer()->minValue(2000)->maxValue(2100)->default(now()->year)->required(),
        Select::make('month')->label('Μήνας')->options(collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => sprintf('%02d', $m)])->all())->default(now()->month)->required(),
        TextInput::make('notes')->label('Παρατηρήσεις')->maxLength(1000)->columnSpanFull(),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        TextColumn::make('book.name')->label('Βιβλίο')->sortable(), TextColumn::make('year')->label('Έτος')->sortable(),
        TextColumn::make('month')->label('Μήνας')->formatStateUsing(fn (int $state) => sprintf('%02d', $state))->sortable(),
        TextColumn::make('entries_count')->label('Εγγραφές')->counts('entries'), TextColumn::make('previewed_at')->label('Προεπισκόπηση')->dateTime('d/m/Y H:i'),
        TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn (string $state) => DepreciationPeriod::STATUSES[$state]),
    ])->recordActions([
        Action::make('preview')->label('Προεπισκόπηση')->icon(Heroicon::OutlinedEye)->visible(fn (DepreciationPeriod $r) => $r->status === 'draft' && static::canPerform('preview'))->action(function (DepreciationPeriod $r): void { abort_unless(static::canPerform('preview'), 403); $n = app(DepreciationService::class)->preview($r)->count(); Notification::make()->title("Δημιουργήθηκαν {$n} προσωρινές εγγραφές.")->success()->send(); }),
        Action::make('post')->label('Οριστικοποίηση')->icon(Heroicon::OutlinedCheckCircle)->color('success')->requiresConfirmation()->modalDescription('Η ενέργεια ενημερώνει τις σωρευμένες αποσβέσεις των παγίων.')->visible(fn (DepreciationPeriod $r) => $r->status === 'draft' && static::canPerform('post'))->action(function (DepreciationPeriod $r): void { abort_unless(static::canPerform('post'), 403); $n = app(DepreciationService::class)->post($r, auth()->id()); Notification::make()->title("Οριστικοποιήθηκαν {$n} εγγραφές.")->success()->send(); }),
        Action::make('lock')->label('Κλείδωμα')->icon(Heroicon::OutlinedLockClosed)->color('warning')->requiresConfirmation()->visible(fn (DepreciationPeriod $r) => $r->status === 'posted' && static::canPerform('lock'))->action(function (DepreciationPeriod $r): void { abort_unless(static::canPerform('lock'), 403); app(DepreciationService::class)->lock($r); Notification::make()->title('Η περίοδος κλειδώθηκε.')->success()->send(); }),
        EditAction::make()->visible(fn (DepreciationPeriod $r) => $r->status === 'draft'),
    ])->headerActions([Action::make('reports')->label('Οικονομικές αναφορές')->icon(Heroicon::OutlinedDocumentChartBar)->url(route('depreciation.reports'))])->defaultSort('year', 'desc')->defaultSort('month', 'desc'); }
    public static function getPages(): array { return ['index' => ListDepreciationPeriods::route('/'), 'create' => CreateDepreciationPeriod::route('/create'), 'edit' => EditDepreciationPeriod::route('/{record}/edit')]; }
}
