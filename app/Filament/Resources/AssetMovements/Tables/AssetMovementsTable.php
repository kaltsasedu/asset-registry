<?php

namespace App\Filament\Resources\AssetMovements\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use App\Exports\TableExport;
use App\Models\AssetMovement;
use App\Filament\Resources\AssetMovements\AssetMovementResource;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Notifications\Notification;

class AssetMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('asset.asset_code')
                    ->label('Κωδικός Παγίου')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('asset.name')
                    ->label('Πάγιο')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('fromLocation.name')
                    ->label('Από Τοποθεσία')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('toLocation.name')
                    ->label('Προς Τοποθεσία')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('toDepartment.name')->label('Προς Τμήμα')->toggleable(),

                TextColumn::make('toCustodian.name')->label('Παραλαμβάνων υπεύθυνος')->toggleable(),

                TextColumn::make('reason')->label('Αιτία')->limit(45)->toggleable(),

                TextColumn::make('status')->label('Κατάσταση')->badge()->formatStateUsing(fn (string $state): string => [
                    'draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε',
                ][$state] ?? $state),

                TextColumn::make('movement_date')
                    ->label('Ημερομηνία')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Σχόλια')
                    ->limit(50),

                TextColumn::make('created_at')
                    ->label('Καταχώριση')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
                SelectFilter::make('asset_id')->label('Πάγιο')->relationship('asset', 'name'),
                SelectFilter::make('to_location_id')->label('Προς τοποθεσία')->relationship('toLocation', 'name'),
                SelectFilter::make('status')->label('Κατάσταση')->options(['draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε']),
            ])

            ->recordActions([
                Action::make('submit')->label('Προς έγκριση')->icon('heroicon-o-paper-airplane')->visible(fn (AssetMovement $record): bool => $record->status === 'draft' && AssetMovementResource::canPerform('submit'))
                    ->requiresConfirmation()->action(function (AssetMovement $record): void { abort_unless(AssetMovementResource::canPerform('submit'), 403); $record->update(['status' => 'pending_approval', 'submitted_at' => now()]); Notification::make()->title('Η μετακίνηση υποβλήθηκε για έγκριση.')->success()->send(); }),
                Action::make('approve')->label('Έγκριση')->icon('heroicon-o-check-circle')->color('success')->visible(fn (AssetMovement $record): bool => $record->status === 'pending_approval' && AssetMovementResource::canPerform('approve'))
                    ->requiresConfirmation()->action(function (AssetMovement $record): void { abort_unless(AssetMovementResource::canPerform('approve'), 403); $record->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]); Notification::make()->title('Η μετακίνηση εγκρίθηκε.')->success()->send(); }),
                Action::make('confirm_delivery')->label('Επιβεβαίωση παράδοσης')->icon('heroicon-o-hand-raised')->visible(fn (AssetMovement $record): bool => $record->status === 'approved' && $record->delivered_confirmed_at === null && AssetMovementResource::canPerform('confirm_delivery'))
                    ->requiresConfirmation()->action(function (AssetMovement $record): void { abort_unless(AssetMovementResource::canPerform('confirm_delivery'), 403); $record->update(['delivered_at' => $record->delivered_at ?? now()->toDateString(), 'delivered_confirmed_at' => now(), 'delivered_confirmed_by' => auth()->id()]); Notification::make()->title('Η παράδοση επιβεβαιώθηκε ηλεκτρονικά.')->success()->send(); }),
                Action::make('confirm_receipt')->label('Επιβεβαίωση παραλαβής')->icon('heroicon-o-clipboard-document-check')->visible(fn (AssetMovement $record): bool => $record->status === 'approved' && $record->delivered_confirmed_at !== null && $record->received_confirmed_at === null && AssetMovementResource::canPerform('confirm_receipt'))
                    ->requiresConfirmation()->action(function (AssetMovement $record): void { abort_unless(AssetMovementResource::canPerform('confirm_receipt'), 403); $record->update(['received_at' => $record->received_at ?? now()->toDateString(), 'received_confirmed_at' => now(), 'received_confirmed_by' => auth()->id()]); Notification::make()->title('Η παραλαβή επιβεβαιώθηκε ηλεκτρονικά.')->success()->send(); }),
                Action::make('complete')->label('Ολοκλήρωση')->icon('heroicon-o-check-badge')->color('success')->visible(fn (AssetMovement $record): bool => $record->status === 'approved' && $record->received_confirmed_at !== null && AssetMovementResource::canPerform('complete'))
                    ->requiresConfirmation()->modalDescription('Θα ενημερωθούν η τοποθεσία, το τμήμα και ο υπεύθυνος του παγίου.')->action(function (AssetMovement $record): void { abort_unless(AssetMovementResource::canPerform('complete'), 403); $record->update(['status' => 'completed']); Notification::make()->title('Η διακίνηση ολοκληρώθηκε και το μητρώο ενημερώθηκε.')->success()->send(); }),
                Action::make('protocol')->label('Πρωτόκολλο PDF')->icon('heroicon-o-document-arrow-down')->url(fn (AssetMovement $record): string => route('asset-movements.protocol', $record))->openUrlInNewTab(),
                EditAction::make()->visible(fn (AssetMovement $record): bool => $record->status === 'draft'),
            ])

            ->toolbarActions([
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->visible(fn (): bool => AssetMovementResource::canPerform('export'))->action(fn () => Excel::download(new TableExport(['Κωδικός Παγίου', 'Πάγιο', 'Από τοποθεσία', 'Προς τοποθεσία', 'Από τμήμα', 'Προς τμήμα', 'Από υπεύθυνο', 'Προς υπεύθυνο', 'Αιτία', 'Ημερομηνία αιτήματος', 'Παράδοση', 'Παραλαβή', 'Κατάσταση', 'Παρατηρήσεις'], AssetMovement::with(['asset', 'fromLocation', 'toLocation', 'fromDepartment', 'toDepartment', 'fromCustodian', 'toCustodian'])->orderByDesc('movement_date')->get()->map(fn ($r) => [$r->asset?->asset_code, $r->asset?->name, $r->fromLocation?->name, $r->toLocation?->name, $r->fromDepartment?->name, $r->toDepartment?->name, $r->fromCustodian?->name, $r->toCustodian?->name, $r->reason, $r->movement_date?->format('d/m/Y'), $r->delivered_at?->format('d/m/Y'), $r->received_at?->format('d/m/Y'), ['draft' => 'Πρόχειρη', 'pending_approval' => 'Προς έγκριση', 'approved' => 'Εγκρίθηκε', 'completed' => 'Ολοκληρώθηκε'][$r->status] ?? $r->status, $r->notes])->all()), 'metakiniseis-pagion.xlsx')),
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn (): bool => AssetMovementResource::canPerform('delete')),
                ]),
            ])

            ->defaultSort('movement_date', 'desc');
    }
}
