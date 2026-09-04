<?php

namespace App\Filament\Resources\SystemBackups;

use App\Filament\Concerns\HasPermissionChecks;
use App\Filament\Resources\SystemBackups\Pages\ListSystemBackups;
use App\Models\ApplicationSetting;
use App\Models\SystemBackup;
use App\Services\SystemBackupService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use Throwable;

class SystemBackupResource extends Resource
{
    use HasPermissionChecks;

    protected static string $permissionPrefix = 'system-backups';
    protected static ?string $model = SystemBackup::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function getNavigationLabel(): string { return 'Αντίγραφα Ασφαλείας'; }
    public static function getModelLabel(): string { return 'Αντίγραφο Ασφαλείας'; }
    public static function getPluralModelLabel(): string { return 'Αντίγραφα Ασφαλείας'; }
    public static function getNavigationGroup(): ?string { return 'Ρυθμίσεις'; }
    public static function getNavigationSort(): ?int { return 7; }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('completed_at')->label('Ημερομηνία')->dateTime('d/m/Y H:i:s')->sortable()->placeholder('—'),
                TextColumn::make('file_name')->label('Αρχείο')->searchable()->copyable(),
                IconColumn::make('includes_files')->label('Συνημμένα')->boolean(),
                TextColumn::make('formatted_size')->label('Μέγεθος'),
                TextColumn::make('status')->label('Κατάσταση')->badge()
                    ->formatStateUsing(fn (string $state): string => SystemBackup::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) { 'completed' => 'success', 'failed' => 'danger', default => 'warning' }),
                TextColumn::make('integrity_status')->label('Ακεραιότητα')->badge()
                    ->formatStateUsing(fn (string $state): string => SystemBackup::INTEGRITY_STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) { 'valid' => 'success', 'invalid' => 'danger', default => 'gray' }),
                TextColumn::make('verified_at')->label('Τελευταίος έλεγχος')->dateTime('d/m/Y H:i')->toggleable(),
                TextColumn::make('creator.name')->label('Δημιουργήθηκε από')->default('Αυτόματα')->toggleable(),
                TextColumn::make('restored_at')->label('Τελευταία επαναφορά')->dateTime('d/m/Y H:i')->placeholder('—')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('checksum_sha256')->label('SHA-256')->copyable()->limit(16)->tooltip(fn (SystemBackup $record): ?string => $record->checksum_sha256)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('error_message')->label('Σφάλμα')->wrap()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->label('Κατάσταση')->options(SystemBackup::STATUSES),
                SelectFilter::make('integrity_status')->label('Ακεραιότητα')->options(SystemBackup::INTEGRITY_STATUSES),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Λήψη')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (SystemBackup $record): bool => $record->status === 'completed' && static::canPerform('download'))
                    ->url(fn (SystemBackup $record): string => route('system-backups.download', $record)),
                Action::make('verify')
                    ->label('Έλεγχος ακεραιότητας')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn (SystemBackup $record): bool => $record->status === 'completed' && static::canPerform('verify'))
                    ->action(function (SystemBackup $record): void {
                        abort_unless(static::canPerform('verify'), 403);
                        $valid = app(SystemBackupService::class)->verify($record);
                        Notification::make()
                            ->title($valid ? 'Το αντίγραφο είναι έγκυρο' : 'Το αντίγραφο δεν είναι έγκυρο')
                            ->body($valid ? 'Το SHA-256 και η δομή του αρχείου επαληθεύτηκαν.' : 'Η επαναφορά από αυτό το αρχείο έχει αποκλειστεί.')
                            ->color($valid ? 'success' : 'danger')
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Επαναφορά')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('danger')
                    ->visible(fn (SystemBackup $record): bool => $record->status === 'completed' && static::canPerform('restore'))
                    ->requiresConfirmation()
                    ->modalHeading('Επαναφορά συστήματος')
                    ->modalDescription('Πριν από την επαναφορά θα δημιουργηθεί αυτόματα νέο πλήρες αντίγραφο της τρέχουσας κατάστασης. Η εφαρμογή θα τεθεί προσωρινά σε λειτουργία συντήρησης.')
                    ->form([
                        Checkbox::make('restore_database')->label('Επαναφορά βάσης δεδομένων')->default(true),
                        Checkbox::make('restore_files')->label('Επαναφορά συνημμένων αρχείων')->default(true),
                        TextInput::make('confirmation')->label('Πληκτρολογήστε ΕΠΑΝΑΦΟΡΑ για επιβεβαίωση')->required(),
                    ])
                    ->action(function (SystemBackup $record, array $data): void {
                        abort_unless(static::canPerform('restore'), 403);
                        if (($data['confirmation'] ?? '') !== 'ΕΠΑΝΑΦΟΡΑ') {
                            throw ValidationException::withMessages(['confirmation' => 'Πληκτρολογήστε ακριβώς ΕΠΑΝΑΦΟΡΑ.']);
                        }

                        try {
                            app(SystemBackupService::class)->restore(
                                $record,
                                (bool) ($data['restore_database'] ?? false),
                                (bool) ($data['restore_files'] ?? false),
                                (int) auth()->id(),
                            );
                            activity('Αντίγραφα Ασφαλείας')->event('restored')->causedBy(auth()->user())
                                ->withProperties(['backup' => $record->file_name])->log('Ολοκληρώθηκε επαναφορά συστήματος.');
                            Notification::make()->title('Η επαναφορά ολοκληρώθηκε')->body('Η εφαρμογή επανήλθε σε κανονική λειτουργία.')->success()->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Η επαναφορά απέτυχε')->body($exception->getMessage())->danger()->persistent()->send();
                        }
                    }),
                Action::make('delete')
                    ->label('Διαγραφή')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (): bool => static::canPerform('delete'))
                    ->requiresConfirmation()
                    ->modalDescription('Το αρχείο και η εγγραφή ιστορικού θα διαγραφούν οριστικά.')
                    ->action(function (SystemBackup $record): void {
                        abort_unless(static::canPerform('delete'), 403);
                        app(SystemBackupService::class)->delete($record);
                    }),
            ])
            ->toolbarActions([
                Action::make('create_backup')
                    ->label('Νέο αντίγραφο')
                    ->icon('heroicon-o-circle-stack')
                    ->visible(fn (): bool => static::canPerform('create'))
                    ->form([
                        Checkbox::make('include_files')->label('Συμπερίληψη συνημμένων αρχείων')
                            ->default(fn (): bool => (bool) ApplicationSetting::current()->backup_include_files),
                    ])
                    ->action(function (array $data): void {
                        abort_unless(static::canPerform('create'), 403);
                        try {
                            $backup = app(SystemBackupService::class)->create((int) auth()->id(), (bool) ($data['include_files'] ?? false));
                            Notification::make()->title('Το αντίγραφο δημιουργήθηκε')->body($backup->file_name . ' · ' . $backup->formatted_size)->success()->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()->title('Η δημιουργία αντιγράφου απέτυχε')->body($exception->getMessage())->danger()->persistent()->send();
                        }
                    }),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListSystemBackups::route('/')];
    }
}
