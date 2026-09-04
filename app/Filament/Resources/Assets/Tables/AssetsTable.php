<?php

namespace App\Filament\Resources\Assets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use App\Exports\AssetsExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Asset;
use App\Models\AssetStatusTransaction;
use App\Filament\Resources\AssetDocuments\AssetDocumentResource;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use App\Services\AssetQrLabelPdfService;
use Illuminate\Database\Eloquent\Collection;

class AssetsTable
{
    public static function configure(Table $table): Table
    {
        return $table

            ->columns([

                TextColumn::make('asset_code')
                    ->label('Κωδικός')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('name')
                    ->label('Περιγραφή')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('category.name')
                    ->label('Κατηγορία')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('supplier.name')
                    ->label('Προμηθευτής')
                    ->toggleable(),

                TextColumn::make('procurementContract.contract_number')->label('Σύμβαση')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fundingSource.name')->label('Χρηματοδότηση')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('costCenter.name')->label('Κέντρο κόστους')->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('location.name')
                    ->label('Τοποθεσία')
                    ->toggleable(),

                TextColumn::make('department.name')
                    ->label('Τμήμα')
                    ->toggleable(),
                TextColumn::make('custodian.name')->label('Υπεύθυνος')->toggleable(),

                TextColumn::make('purchase_value')
                    ->label('Αξία Κτήσης')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('annual_depreciation')
                    ->label('Ετήσια Απόσβεση')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('remaining_value')
                    ->label('Αναπόσβεστη Αξία')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('status')
                    ->label('Κατάσταση')
                    ->formatStateUsing(fn (string $state): string => AssetStatusTransaction::STATUSES[$state] ?? $state)
                    ->colors([
                        'info' => 'under_receipt',
                        'success' => 'in_use',
                        'warning' => ['under_repair', 'in_inventory', 'pending_disposal'],
                        'danger' => 'retired',
                        'gray' => 'sold',
                    ]),

                TextColumn::make('purchase_date')
                    ->label('Ημ. Απόκτησης')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Καταχώριση')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),

            ])

            ->filters([
                SelectFilter::make('status')->label('Κατάσταση')->options(AssetStatusTransaction::STATUSES),
                SelectFilter::make('asset_category_id')->label('Κατηγορία')->relationship('category', 'name'),
                SelectFilter::make('location_id')->label('Τοποθεσία')->relationship('location', 'name'),
                SelectFilter::make('department_id')->label('Τμήμα')->relationship('department', 'name'),
                SelectFilter::make('procurement_contract_id')->label('Σύμβαση')->relationship('procurementContract', 'contract_number'),
                SelectFilter::make('funding_source_id')->label('Χρηματοδότηση')->relationship('fundingSource', 'name'),
                SelectFilter::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name'),
                Filter::make('without_custodian')->label('Χωρίς υπεύθυνο')->query(fn ($query) => $query->whereNull('asset_custodian_id')),
                Filter::make('without_serial')->label('Χωρίς serial number')->query(fn ($query) => $query->where(fn ($q) => $q->whereNull('serial_number')->orWhere('serial_number', ''))),
                Filter::make('without_invoice')->label('Χωρίς τιμολόγιο')->query(fn ($query) => $query->where(fn ($q) => $q->whereNull('invoice_number')->orWhere('invoice_number', ''))),
                Filter::make('without_location')->label('Χωρίς τοποθεσία')->query(fn ($query) => $query->whereNull('location_id')),
            ])

            ->recordActions([
                Action::make('qr_code')->label('QR Code')->icon('heroicon-o-qr-code')->url(fn ($record) => route('assets.qr-code', $record))->openUrlInNewTab(),
                Action::make('documents')->label('Δικαιολογητικά')->icon('heroicon-o-paper-clip')->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-documents.view'))->url(fn (Asset $record): string => AssetDocumentResource::getUrl('index', ['tableFilters' => ['asset_id' => ['value' => $record->id]]])),
                Action::make('lifecycle_history')->label('Ιστορικό κύκλου ζωής')->icon('heroicon-o-clock')->url(fn (Asset $record): string => route('assets.lifecycle', $record))->openUrlInNewTab(),
                Action::make('dossier_pdf')
                    ->label('Φάκελος Παγίου PDF')
                    ->icon('heroicon-o-document-text')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.dossier_export'))
                    ->url(fn (Asset $record): string => route('assets.dossier.pdf', $record))
                    ->openUrlInNewTab(),
                Action::make('dossier_zip')
                    ->label('Πλήρης Φάκελος ZIP')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.dossier_export'))
                    ->url(fn (Asset $record): string => route('assets.dossier.zip', $record)),
                Action::make('change_status')
                    ->label('Μεταβολή κατάστασης')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (Asset $record): bool => ! in_array($record->status, ['retired', 'sold'], true) && (auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.status_change')))
                    ->form([
                        Select::make('to_status')->label('Νέα κατάσταση')->options([
                            'under_receipt' => 'Υπό παραλαβή',
                            'in_use' => 'Σε χρήση',
                            'under_repair' => 'Σε επισκευή',
                            'in_inventory' => 'Σε απογραφή',
                            'pending_disposal' => 'Προς απόσυρση',
                        ])->required(),
                        Textarea::make('reason')->label('Αιτία μεταβολής')->required()->maxLength(2000),
                        TextInput::make('document_number')->label('Αριθμός πρωτοκόλλου ή εγγράφου')->required()->maxLength(150),
                        DatePicker::make('document_date')->label('Ημερομηνία εγγράφου')->required()->default(now()),
                        FileUpload::make('document_path')->label('Σχετικό έγγραφο')->disk('local')->directory('asset-transactions')->preserveFilenames()->required(),
                        Textarea::make('notes')->label('Παρατηρήσεις')->maxLength(2000),
                    ])
                    ->action(function (Asset $record, array $data): void {
                        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.status_change'), 403);
                        if ($record->status === $data['to_status']) {
                            return;
                        }

                        $record->statusTransactions()->create([
                            'transaction_type' => 'status_change',
                            'from_status' => $record->status,
                            'to_status' => $data['to_status'],
                            'reason' => $data['reason'],
                            'document_number' => $data['document_number'],
                            'document_date' => $data['document_date'],
                            'document_path' => $data['document_path'],
                            'purchase_value' => $record->purchase_value,
                            'accumulated_depreciation' => $record->accumulated_depreciation,
                            'book_value' => $record->remaining_value,
                            'notes' => $data['notes'] ?? null,
                            'created_by' => auth()->id(),
                        ]);

                        $record->update(['status' => $data['to_status']]);
                    }),
                EditAction::make(),
            ])

            ->toolbarActions([
                Action::make('excel_import')->label('Εισαγωγή Excel')->icon('heroicon-o-arrow-up-tray')->visible(fn (): bool => auth()->user()?->hasFullDataAccess() && (auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.import')))->url(route('assets.import')),
                Action::make('excel_export')->label('Εξαγωγή Excel')->icon('heroicon-o-arrow-down-tray')->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.export'))->action(function () { abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.export'), 403); return Excel::download(new AssetsExport(), 'pagia.xlsx'); }),
                Action::make('pdf_register')->label('PDF Μητρώο Παγίων')->icon('heroicon-o-document-arrow-down')->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.export'))->url(route('assets.register.pdf')),
                BulkActionGroup::make([
                    BulkAction::make('qr_labels')
                        ->label('Εκτύπωση ετικετών QR')
                        ->icon('heroicon-o-qr-code')
                        ->visible(fn (): bool => auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.qr_labels'))
                        ->form([
                            Select::make('layout')
                                ->label('Μορφή ετικετών')
                                ->options([
                                    'small' => 'Μικρές — 24 ανά σελίδα Α4',
                                    'large' => 'Μεγάλες — 10 ανά σελίδα Α4',
                                ])
                                ->default('small')
                                ->required(),
                            TextInput::make('start_position')
                                ->label('Αρχική θέση στο πρώτο φύλλο')
                                ->helperText('1 για νέο φύλλο. Χρησιμοποιήστε επόμενη θέση σε μερικώς χρησιμοποιημένο φύλλο.')
                                ->integer()
                                ->minValue(1)
                                ->maxValue(24)
                                ->default(1)
                                ->required(),
                            Checkbox::make('show_serial')->label('Εμφάνιση σειριακού αριθμού')->default(true),
                            Checkbox::make('show_location')->label('Εμφάνιση τοποθεσίας'),
                            Checkbox::make('show_department')->label('Εμφάνιση τμήματος'),
                            Checkbox::make('show_custodian')->label('Εμφάνιση υπευθύνου παγίου'),
                        ])
                        ->deselectRecordsAfterCompletion()
                        ->action(fn (Collection $records, array $data) => app(AssetQrLabelPdfService::class)->download($records, $data)),
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('id', 'desc');
    }
}
