<?php

namespace App\Filament\Resources\AssetReceipts\RelationManagers;

use App\Models\AssetCategory;
use App\Models\AssetReceiptLine;
use App\Models\DepreciationBook;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetReceiptLinesRelationManager extends RelationManager
{
    protected static string $relationship = 'lines';
    protected static ?string $title = 'Γραμμές παραλαβής — πάγια προς δημιουργία';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Στοιχεία γραμμής')->columns(3)->schema([
                TextInput::make('description')->label('Περιγραφή παγίου')->required()->columnSpanFull(),
                TextInput::make('quantity')->label('Ποσότητα')->numeric()->minValue(1)->default(1)->required(),
                TextInput::make('unit_value')->label('Αξία ανά πάγιο χωρίς ΦΠΑ')->numeric()->suffix('€')->required(),
                TextInput::make('vat_amount')->label('ΦΠΑ ανά πάγιο')->numeric()->suffix('€')->default(0),
                Select::make('asset_category_id')->label('Κατηγορία')->relationship('category', 'name')->searchable()->preload()->live()->required()
                    ->afterStateUpdated(function ($state, Set $set): void { $category = AssetCategory::find($state); if ($category) { $set('depreciation_rate', $category->default_rate); $set('depreciation_method', $category->default_depreciation_method); $set('depreciation_timing', $category->default_depreciation_timing); } }),
                Select::make('depreciation_book_id')->label('Βιβλίο αποσβέσεων')->options(fn (): array => DepreciationBook::query()->where('is_active', true)->orderByDesc('is_default')->pluck('name', 'id')->all())->default(fn (): ?int => DepreciationBook::query()->where('is_default', true)->value('id'))->searchable()->preload()->required(),
                Select::make('location_id')->label('Αρχική τοποθεσία')->relationship('location', 'name')->searchable()->preload(),
                Select::make('department_id')->label('Αρχικό τμήμα')->relationship('department', 'name')->searchable()->preload(),
                Select::make('asset_custodian_id')->label('Αρχικός υπεύθυνος')->relationship('custodian', 'name')->searchable()->preload(),
                TextInput::make('serial_number')->label('Serial number')->helperText('Συμπληρώνεται όταν η ποσότητα είναι 1.'),
            ]),
            Section::make('Απόσβεση και τεχνικά στοιχεία')->columns(3)->schema([
                DatePicker::make('depreciation_start_date')->label('Έναρξη απόσβεσης')->default(now())->required(),
                TextInput::make('depreciation_rate')->label('Συντελεστής')->numeric()->suffix('%')->required(),
                TextInput::make('useful_life_years')->label('Χρήσιμη ζωή')->numeric()->suffix('έτη')->required(),
                Select::make('depreciation_method')->label('Μέθοδος')->options(['straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική'])->default('straight_line')->required(),
                Select::make('depreciation_timing')->label('Χρονισμός')->options(['full_month' => 'Πλήρης μήνας', 'next_month' => 'Από τον επόμενο μήνα'])->default('full_month')->required(),
                TextInput::make('residual_value')->label('Υπολειμματική αξία ανά πάγιο')->numeric()->suffix('€')->default(0),
                TextInput::make('manufacturer')->label('Κατασκευαστής'),
                TextInput::make('model')->label('Μοντέλο'),
                Textarea::make('notes')->label('Παρατηρήσεις')->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('description')->label('Περιγραφή')->searchable(),
            TextColumn::make('quantity')->label('Ποσότητα'),
            TextColumn::make('unit_value')->label('Αξία/πάγιο')->money('EUR'),
            TextColumn::make('category.name')->label('Κατηγορία'),
            TextColumn::make('location.name')->label('Τοποθεσία')->toggleable(),
            TextColumn::make('assets_count')->counts('assets')->label('Δημιουργημένα πάγια'),
        ])->headerActions([CreateAction::make()->label('Νέα γραμμή παραλαβής')->visible(fn (): bool => $this->getOwnerRecord()->status === 'draft')])
          ->recordActions([EditAction::make()->label('Επεξεργασία')->visible(fn (): bool => $this->getOwnerRecord()->status === 'draft'), DeleteAction::make()->visible(fn (): bool => $this->getOwnerRecord()->status === 'draft')]);
    }
}
