<?php

namespace App\Filament\Resources\FundingSources;

use App\Exports\TableExport;
use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\FundingSources\Pages\CreateFundingSource;
use App\Filament\Resources\FundingSources\Pages\EditFundingSource;
use App\Filament\Resources\FundingSources\Pages\ListFundingSources;
use App\Models\FundingSource;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class FundingSourceResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'funding-sources';
    protected static ?string $model = FundingSource::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    public static function getNavigationLabel(): string { return 'Πηγές Χρηματοδότησης'; }
    public static function getModelLabel(): string { return 'Πηγή Χρηματοδότησης'; }
    public static function getPluralModelLabel(): string { return 'Πηγές Χρηματοδότησης'; }
    public static function getNavigationGroup(): ?string { return 'Μητρώα'; }
    public static function getNavigationSort(): ?int { return 6; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Στοιχεία χρηματοδότησης')->columns(2)->schema([
            TextInput::make('code')->label('Κωδικός')->required()->maxLength(80)->unique(ignoreRecord: true),
            TextInput::make('name')->label('Ονομασία')->required()->maxLength(255),
            Select::make('funding_type')->label('Τύπος χρηματοδότησης')->options(FundingSource::TYPES)->default('regular_budget')->required(),
            TextInput::make('program')->label('Πρόγραμμα / Άξονας'),
            TextInput::make('project_code')->label('Κωδικός έργου'),
            TextInput::make('ops_code')->label('Κωδικός ΟΠΣ'),
            Checkbox::make('is_active')->label('Ενεργή')->default(true),
            Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Κωδικός')->searchable()->sortable(),
            TextColumn::make('name')->label('Ονομασία')->searchable()->sortable(),
            TextColumn::make('funding_type')->label('Τύπος')->badge()->formatStateUsing(fn (string $state): string => FundingSource::TYPES[$state] ?? $state),
            TextColumn::make('program')->label('Πρόγραμμα')->toggleable(),
            TextColumn::make('project_code')->label('Κωδ. έργου')->toggleable(),
            TextColumn::make('ops_code')->label('ΟΠΣ')->toggleable(),
            IconColumn::make('is_active')->label('Ενεργή')->boolean(),
        ])->filters([SelectFilter::make('funding_type')->label('Τύπος')->options(FundingSource::TYPES)])
          ->recordActions([EditAction::make()->label('Επεξεργασία')])
          ->toolbarActions([Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->visible(fn (): bool => static::canPerform('export'))->action(fn () => Excel::download(new TableExport(
              ['Κωδικός', 'Ονομασία', 'Τύπος', 'Πρόγραμμα', 'Κωδικός έργου', 'Κωδικός ΟΠΣ', 'Ενεργή'],
              FundingSource::orderBy('code')->get()->map(fn ($row) => [$row->code, $row->name, FundingSource::TYPES[$row->funding_type] ?? $row->funding_type, $row->program, $row->project_code, $row->ops_code, $row->is_active ? 'Ναι' : 'Όχι'])->all(),
              'Πηγές Χρηματοδότησης'
          ), 'piges-xrimatodotisis.xlsx'))]);
    }

    public static function getPages(): array { return ['index' => ListFundingSources::route('/'), 'create' => CreateFundingSource::route('/create'), 'edit' => EditFundingSource::route('/{record}/edit')]; }
}
