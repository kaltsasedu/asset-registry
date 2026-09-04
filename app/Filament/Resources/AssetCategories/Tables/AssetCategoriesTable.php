<?php

namespace App\Filament\Resources\AssetCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use App\Exports\TableExport;
use App\Models\AssetCategory;
use Maatwebsite\Excel\Facades\Excel;

class AssetCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('name')
                    ->label('Κατηγορία')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('default_rate')
                    ->label('Συντ. Απόσβεσης %')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('default_depreciation_method')
                    ->label('Μέθοδος απόσβεσης')
                    ->formatStateUsing(fn (string $state): string => ['straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική'][$state] ?? $state)
                    ->toggleable(),

                TextColumn::make('default_depreciation_timing')
                    ->label('Χρονισμός')
                    ->formatStateUsing(fn (string $state): string => ['full_month' => 'Πλήρης μήνας', 'next_month' => 'Επόμενος μήνας'][$state] ?? $state)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Δημιουργία')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

            ])

            ->filters([
                Filter::make('has_depreciation_rate')->label('Με συντελεστή απόσβεσης')->query(fn ($query) => $query->whereNotNull('default_rate')),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->action(fn () => Excel::download(new TableExport(['Κατηγορία', 'Συντελεστής Απόσβεσης (%)', 'Ημερομηνία δημιουργίας'], AssetCategory::orderBy('name')->get()->map(fn ($r) => [$r->name, $r->default_rate, $r->created_at?->format('d/m/Y H:i')])->all()), 'kathgories-pagion.xlsx')),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
