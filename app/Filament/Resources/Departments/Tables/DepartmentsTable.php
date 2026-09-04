<?php

namespace App\Filament\Resources\Departments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use App\Exports\TableExport;
use App\Models\Department;
use Maatwebsite\Excel\Facades\Excel;

class DepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('name')
                    ->label('Τμήμα')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Περιγραφή')
                    ->limit(50)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Δημιουργία')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

            ])

            ->filters([
                Filter::make('has_description')->label('Με περιγραφή')->query(fn ($query) => $query->whereNotNull('description')),
            ])

            ->recordActions([
                EditAction::make(),
            ])

            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->action(fn () => Excel::download(new TableExport(['Τμήμα', 'Περιγραφή', 'Ημερομηνία δημιουργίας'], Department::orderBy('name')->get()->map(fn ($r) => [$r->name, $r->description, $r->created_at?->format('d/m/Y H:i')])->all()), 'tmimata.xlsx')),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
