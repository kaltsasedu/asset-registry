<?php

namespace App\Filament\Resources\AssetCustodians;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\AssetCustodians\Pages\CreateAssetCustodian;
use App\Filament\Resources\AssetCustodians\Pages\EditAssetCustodian;
use App\Filament\Resources\AssetCustodians\Pages\ListAssetCustodians;
use App\Models\AssetCustodian;
use App\Exports\TableExport;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class AssetCustodianResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'asset-custodians';
    protected static ?string $model = AssetCustodian::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    public static function getNavigationLabel(): string { return 'Υπεύθυνοι Παγίων'; }
    public static function getModelLabel(): string { return 'Υπεύθυνος Παγίου'; }
    public static function getPluralModelLabel(): string { return 'Υπεύθυνοι Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Μητρώα'; }
    public static function getNavigationSort(): ?int { return 5; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Ονοματεπώνυμο')->required()->maxLength(150),
            TextInput::make('email')->label('Ηλεκτρονικό ταχυδρομείο')->email()->maxLength(255),
            TextInput::make('phone')->label('Τηλέφωνο')->tel()->maxLength(50),
            Select::make('department_id')->label('Τμήμα')
                ->options(fn (): array => Department::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->preload()->native(false),
            Select::make('user_id')->label('Συνδεδεμένος χρήστης')
                ->helperText('Προαιρετική σύνδεση με λογαριασμό της εφαρμογής.')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->preload()->native(false)
                ->disabled(fn (): bool => ! auth()->user()?->hasRole('Διαχειριστής') && ! auth()->user()?->can('users.manage_data_scope')),
            Toggle::make('active')->label('Ενεργός')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('Ονοματεπώνυμο')->searchable()->sortable(),
            TextColumn::make('department.name')->label('Τμήμα')->placeholder('—')->toggleable(),
            TextColumn::make('user.name')->label('Συνδεδεμένος χρήστης')->placeholder('—')->toggleable(),
            TextColumn::make('email')->label('Ηλεκτρονικό ταχυδρομείο')->toggleable(),
            TextColumn::make('phone')->label('Τηλέφωνο')->toggleable(isToggledHiddenByDefault: true),
            IconColumn::make('active')->label('Ενεργός')->boolean(),
        ])->filters([
            TernaryFilter::make('active')->label('Ενεργός'),
        ])->recordActions([
            EditAction::make()->label('Επεξεργασία'),
        ])->toolbarActions([
            Action::make('excel_export')->label('Εξαγωγή Excel')->icon(Heroicon::OutlinedArrowDownTray)->action(fn () => Excel::download(new TableExport(['Ονοματεπώνυμο', 'Τμήμα', 'Συνδεδεμένος χρήστης', 'Ηλεκτρονικό ταχυδρομείο', 'Τηλέφωνο', 'Ενεργός'], AssetCustodian::with(['department', 'user'])->orderBy('name')->get()->map(fn ($r) => [$r->name, $r->department?->name, $r->user?->name, $r->email, $r->phone, $r->active ? 'Ναι' : 'Όχι'])->all()), 'ypeythynoi-pagion.xlsx')),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAssetCustodians::route('/'),
            'create' => CreateAssetCustodian::route('/create'),
            'edit' => EditAssetCustodian::route('/{record}/edit'),
        ];
    }
}
