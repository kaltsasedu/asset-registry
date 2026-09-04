<?php

namespace App\Filament\Resources\Locations\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use App\Exports\TableExport;
use App\Models\Location;
use Maatwebsite\Excel\Facades\Excel;

class LocationsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('name')
                    ->label('Τοποθεσία')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('building')
                    ->label('Κτίριο')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('floor')
                    ->label('Όροφος')
                    ->toggleable(),

                TextColumn::make('room')
                    ->label('Γραφείο')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Δημιουργία')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

            ])

            ->filters([
                SelectFilter::make('building')->label('Κτίριο')->options(fn () => \App\Models\Location::query()->whereNotNull('building')->distinct()->orderBy('building')->pluck('building', 'building')->all()),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->action(fn () => Excel::download(new TableExport(['Τοποθεσία', 'Κτίριο', 'Όροφος', 'Γραφείο', 'Ημερομηνία δημιουργίας'], Location::orderBy('name')->get()->map(fn ($r) => [$r->name, $r->building, $r->floor, $r->room, $r->created_at?->format('d/m/Y H:i')])->all()), 'topothesies.xlsx')),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
