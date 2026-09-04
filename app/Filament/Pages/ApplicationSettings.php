<?php

namespace App\Filament\Pages;

use App\Models\ApplicationSetting;
use BackedEnum;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Concerns\RestrictsFileUploadsToSchemaComponents;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ApplicationSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    use RestrictsFileUploadsToSchemaComponents;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;
    protected string $view = 'filament.pages.application-settings';
    public ApplicationSetting $setting;
    public ?array $data = [];

    public static function getNavigationLabel(): string { return 'Ρυθμίσεις Φορέα'; }
    public function getTitle(): string { return 'Ρυθμίσεις Φορέα και Εφαρμογής'; }
    public static function getNavigationGroup(): ?string { return 'Ρυθμίσεις'; }
    public static function getNavigationSort(): ?int { return 6; }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('application-settings.manage');
    }

    public function mount(): void
    {
        $this->setting = ApplicationSetting::current();
        $this->form->fill($this->setting->toArray());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->setting)
            ->statePath('data')
            ->components([
                Section::make('Στοιχεία φορέα')
                    ->description('Χρησιμοποιούνται στην κεφαλίδα της εφαρμογής, στις επίσημες αναφορές, τα πρωτόκολλα και τις ετικέτες παγίων.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('organization_name')->label('Επωνυμία φορέα')->required()->maxLength(200)->columnSpanFull(),
                        TextInput::make('tax_id')->label('ΑΦΜ')->maxLength(20),
                        TextInput::make('phone')->label('Τηλέφωνο')->tel()->maxLength(50),
                        TextInput::make('email')->label('Ηλεκτρονική διεύθυνση')->email()->maxLength(150),
                        Textarea::make('address')->label('Διεύθυνση')->rows(2)->maxLength(500),
                        FileUpload::make('logo_path')->label('Λογότυπο φορέα')->disk('local')->directory('application-settings')->image()->maxSize(2048),
                    ]),

                Section::make('Αναφορές και έγγραφα')
                    ->columns(2)
                    ->schema([
                        Checkbox::make('show_logo_on_reports')->label('Εμφάνιση λογοτύπου σε PDF')->default(true),
                        Textarea::make('report_footer')->label('Υποσέλιδο αναφορών')->rows(2)->maxLength(500)->columnSpanFull(),
                    ]),

                Section::make('Αρίθμηση παγίων')
                    ->description('Οι αλλαγές εφαρμόζονται μόνο στους νέους κωδικούς και δεν μεταβάλλουν τα ήδη καταχωρισμένα πάγια.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('asset_code_prefix')->label('Πρόθεμα κωδικού')->required()->maxLength(20)->rules(['regex:/^[A-Za-zΑ-Ωα-ω0-9-]+$/u']),
                        TextInput::make('asset_code_digits')->label('Πλήθος ψηφίων')->integer()->minValue(3)->maxValue(12)->required(),
                    ]),

                Section::make('Όρια προειδοποιήσεων')
                    ->description('Πλήθος ημερών πριν από το γεγονός. Οι τιμές χρησιμοποιούνται στις ειδοποιήσεις και στο Κέντρο Ενεργειών.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('warranty_warning_days')->label('Λήξη εγγύησης')->integer()->minValue(0)->maxValue(365)->suffix('ημέρες')->required(),
                        TextInput::make('insurance_warning_days')->label('Λήξη ασφάλισης')->integer()->minValue(0)->maxValue(365)->suffix('ημέρες')->required(),
                        TextInput::make('maintenance_warning_days')->label('Επερχόμενη συντήρηση')->integer()->minValue(0)->maxValue(365)->suffix('ημέρες')->required(),
                        TextInput::make('receipt_overdue_days')->label('Εκκρεμής κεφαλαιοποίηση παραλαβής')->integer()->minValue(1)->maxValue(365)->suffix('ημέρες')->required(),
                    ]),

                Section::make('Αυτόματα αντίγραφα ασφαλείας')
                    ->description('Το αντίγραφο αποθηκεύεται ιδιωτικά και περιλαμβάνει τη βάση δεδομένων και, προαιρετικά, όλα τα συνημμένα αρχεία.')
                    ->columns(2)
                    ->schema([
                        Checkbox::make('backup_enabled')->label('Ενεργοποίηση αυτόματων αντιγράφων')->default(true),
                        Checkbox::make('backup_include_files')->label('Συμπερίληψη συνημμένων αρχείων')->default(true),
                        Select::make('backup_frequency')->label('Συχνότητα')->options([
                            'daily' => 'Καθημερινά',
                            'weekly' => 'Κάθε επτά ημέρες',
                        ])->default('daily')->required(),
                        TimePicker::make('backup_time')->label('Ώρα εκτέλεσης (Ελλάδα)')->seconds(false)->default('02:00')->required(),
                        TextInput::make('backup_retention_days')->label('Χρόνος διατήρησης')->integer()->minValue(1)->maxValue(3650)->suffix('ημέρες')->default(30)->required(),
                    ]),
            ]);
    }

    public function save(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->setting->update($this->form->getState());
        $this->setting->refresh();
        $this->form->fill($this->setting->toArray());

        Notification::make()
            ->title('Οι ρυθμίσεις αποθηκεύτηκαν')
            ->body('Οι νέες τιμές εφαρμόζονται άμεσα στην κεφαλίδα, στις αναφορές, τις ειδοποιήσεις και στους νέους κωδικούς παγίων.')
            ->success()
            ->send();
    }
}
