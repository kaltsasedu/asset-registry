<?php

namespace App\Filament\Resources\Assets\RelationManagers;

use App\Models\DepreciationBook;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AssetDepreciationProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'depreciationProfiles';
    protected static ?string $title = 'Βιβλία και προφίλ αποσβέσεων';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('depreciation_book_id')->label('Βιβλίο αποσβέσεων')->options(fn (): array => DepreciationBook::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())->searchable()->preload()->required(),
            DatePicker::make('depreciation_start_date')->label('Έναρξη απόσβεσης')->required()->default(fn () => $this->getOwnerRecord()->depreciation_start_date),
            Select::make('depreciation_method')->label('Μέθοδος')->options(['straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική'])->default('straight_line')->required(),
            Select::make('depreciation_timing')->label('Χρονισμός')->options(['full_month' => 'Πλήρης μήνας', 'next_month' => 'Από τον επόμενο μήνα'])->default('full_month')->required(),
            TextInput::make('depreciation_rate')->label('Συντελεστής')->numeric()->suffix('%')->required(),
            TextInput::make('useful_life_years')->label('Χρήσιμη ζωή')->numeric()->suffix('έτη')->required(),
            TextInput::make('residual_value')->label('Υπολειμματική αξία')->numeric()->suffix('€')->default(0)->required(),
            TextInput::make('accumulated_depreciation')->label('Σωρευμένες αποσβέσεις')->numeric()->suffix('€')->default(0)->required(),
            TextInput::make('remaining_value')->label('Αναπόσβεστη αξία')->numeric()->suffix('€')->default(fn () => $this->getOwnerRecord()->purchase_value)->required(),
            Select::make('is_active')->label('Ενεργό')->options([1 => 'Ναι', 0 => 'Όχι'])->default(1)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('book.name')->label('Βιβλίο')->sortable(),
            TextColumn::make('depreciation_method')->label('Μέθοδος')->formatStateUsing(fn (string $state): string => ['straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική'][$state] ?? $state),
            TextColumn::make('depreciation_rate')->label('Συντ.')->suffix('%'),
            TextColumn::make('accumulated_depreciation')->label('Σωρευμένες')->money('EUR'),
            TextColumn::make('remaining_value')->label('Αναπόσβεστη')->money('EUR'),
            IconColumn::make('is_active')->label('Ενεργό')->boolean(),
        ])->headerActions([CreateAction::make()->label('Προσθήκη βιβλίου')])->recordActions([
            EditAction::make()->label('Επεξεργασία'),
            DeleteAction::make()->visible(fn ($record): bool => (int) $record->depreciation_book_id !== (int) $this->getOwnerRecord()->depreciation_book_id),
        ]);
    }
}
