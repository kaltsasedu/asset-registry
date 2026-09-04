<?php

namespace App\Filament\Resources\Assets\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\AssetCategory;
use App\Models\DepreciationBook;

class AssetForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Βασικά στοιχεία παγίου')
                    ->description('Ταυτοποίηση, κατηγοριοποίηση και τρέχουσα θέση του παγίου.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('asset_code')
                            ->label('Κωδικός Παγίου')
                            ->disabled()
                            ->dehydrated(),

                        Select::make('status')
                            ->label('Αρχική κατάσταση')
                            ->options([
                                'under_receipt' => 'Υπό παραλαβή',
                                'in_use' => 'Σε χρήση',
                                'under_repair' => 'Σε επισκευή',
                                'in_inventory' => 'Σε απογραφή',
                                'pending_disposal' => 'Προς απόσυρση',
                                'retired' => 'Αποσυρμένο',
                                'sold' => 'Πωλημένο',
                            ])
                            ->default('under_receipt')
                            ->visible(fn (string $operation): bool => $operation === 'create')
                            ->helperText('Οι επόμενες αλλαγές κατάστασης γίνονται από την ενέργεια «Μεταβολή κατάστασης».')
                            ->required(),

                        TextInput::make('name')
                            ->label('Περιγραφή Παγίου')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Αναλυτική Περιγραφή')
                            ->columnSpanFull(),

                        Select::make('asset_category_id')
                            ->label('Κατηγορία')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $category = AssetCategory::find($state);

                                if ($category) {
                                    $set('depreciation_rate', $category->default_rate);
                                    $set('depreciation_method', $category->default_depreciation_method);
                                    $set('depreciation_timing', $category->default_depreciation_timing);
                                }
                            }),

                        Select::make('supplier_id')
                            ->label('Προμηθευτής')
                            ->relationship('supplier', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('location_id')
                            ->label('Τοποθεσία')
                            ->relationship('location', 'name')
                            ->searchable()
                            ->preload(),

                        Select::make('department_id')
                            ->label('Τμήμα')
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('asset_custodian_id')->label('Υπεύθυνος Παγίου')->relationship('custodian', 'name')->searchable()->preload(),
                    ]),

                Section::make('Τεχνικά χαρακτηριστικά')
                    ->columns(3)
                    ->schema([
                        TextInput::make('serial_number')
                            ->label('Σειριακός Αριθμός'),

                        TextInput::make('manufacturer')
                            ->label('Κατασκευαστής'),

                        TextInput::make('model')
                            ->label('Μοντέλο'),
                    ]),

                Section::make('Αξία και αποσβέσεις')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('purchase_date')
                            ->label('Ημερομηνία Απόκτησης')
                            ->required(),

                        DatePicker::make('depreciation_start_date')
                            ->label('Έναρξη Απόσβεσης')
                            ->required(),

                        TextInput::make('purchase_value')
                            ->label('Αξία Κτήσης')
                            ->numeric()
                            ->suffix('€')
                            ->required(),

                        TextInput::make('vat_amount')
                            ->label('ΦΠΑ')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),

                        TextInput::make('residual_value')
                            ->label('Υπολειμματική Αξία')
                            ->numeric()
                            ->suffix('€')
                            ->default(0),

                        TextInput::make('depreciation_rate')
                            ->label('Συντελεστής Απόσβεσης')
                            ->numeric()
                            ->suffix('%')
                            ->required(),

                        Select::make('depreciation_method')->label('Μέθοδος απόσβεσης')->options([
                            'straight_line' => 'Σταθερή', 'declining_balance' => 'Φθίνουσα', 'none' => 'Μηδενική',
                        ])->default('straight_line')->required(),

                        Select::make('depreciation_timing')->label('Χρονισμός απόσβεσης')->options([
                            'full_month' => 'Πλήρης μήνας', 'next_month' => 'Από τον επόμενο μήνα',
                        ])->default('full_month')->required(),

                        Select::make('depreciation_book_id')->label('Βιβλίο αποσβέσεων')
                            ->options(fn (): array => DepreciationBook::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->pluck('name', 'id')->all())
                            ->default(fn (): ?int => DepreciationBook::query()->where('is_default', true)->value('id'))
                            ->searchable()->preload()->required(),

                        TextInput::make('useful_life_years')
                            ->label('Χρήσιμη Ζωή')
                            ->numeric()
                            ->suffix('έτη')
                            ->required(),
                    ]),

                Section::make('Εγγύηση και παραστατικό κτήσης')
                    ->columns(2)
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Αριθμός Τιμολογίου'),

                        DatePicker::make('invoice_date')
                            ->label('Ημερομηνία Τιμολογίου'),

                        Select::make('procurement_contract_id')->label('Σύμβαση')->relationship('procurementContract', 'title')->searchable()->preload(),
                        Select::make('funding_source_id')->label('Πηγή χρηματοδότησης')->relationship('fundingSource', 'name')->searchable()->preload(),
                        Select::make('cost_center_id')->label('Κέντρο κόστους')->relationship('costCenter', 'name')->searchable()->preload(),

                        DatePicker::make('warranty_expires_at')
                            ->label('Λήξη Εγγύησης'),

                        DatePicker::make('warranty_starts_at')->label('Έναρξη Εγγύησης'),
                        TextInput::make('warranty_provider')->label('Πάροχος εγγύησης'),
                        Textarea::make('warranty_terms')->label('Όροι εγγύησης')->columnSpanFull(),
                    ]),
            ]);
    }
}
