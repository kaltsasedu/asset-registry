<?php

namespace App\Filament\Resources\Suppliers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use App\Exports\TableExport;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;

class SuppliersTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('name')
                    ->label('Επωνυμία')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('afm')
                    ->label('ΑΦΜ')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Ηλεκτρονική Διεύθυνση')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('phone')
                    ->label('Τηλέφωνο')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Δημιουργία')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

            ])

            ->filters([
                Filter::make('has_afm')->label('Με ΑΦΜ')->query(fn ($query) => $query->whereNotNull('afm')),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->action(fn () => Excel::download(new TableExport(['Επωνυμία', 'ΑΦΜ', 'Ηλεκτρονική διεύθυνση', 'Τηλέφωνο', 'Ημερομηνία δημιουργίας'], Supplier::orderBy('name')->get()->map(fn ($r) => [$r->name, $r->afm, $r->email, $r->phone, $r->created_at?->format('d/m/Y H:i')])->all()), 'promitheftes.xlsx')),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('name');
    }
}
