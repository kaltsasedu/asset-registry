<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class FinancialReportExport implements Export, WithMultipleSheets
{
    public function __construct(private array $data) {}

    public function sheets(): array
    {
        $dimensionSheet = fn ($items, string $title) => new TableExport(
            [$title, 'Πλήθος παγίων', 'Αξία κτήσης', 'Σωρευμένες αποσβέσεις', 'Αναπόσβεστη αξία'],
            collect($items)->map(fn (array $item) => [$item['label'], $item['assets'], $item['purchase_value'], $item['accumulated_depreciation'], $item['remaining_value']])->all(),
            $title,
        );

        return [
            new TableExport([
                'Κωδικός', 'Πάγιο', 'Κατηγορία', 'Τμήμα', 'Τοποθεσία', 'Πηγή χρηματοδότησης', 'Κέντρο κόστους', 'Σύμβαση', 'Αξία κτήσης', 'Υπολειμματική αξία', 'Σωρευμένες αποσβέσεις', 'Αναπόσβεστη αξία',
            ], $this->data['registerRows']->map(fn (array $row) => [
                $row['code'], $row['name'], $row['category'], $row['department'], $row['location'], $row['funding_source'], $row['cost_center'], $row['contract'], $row['purchase_value'], $row['residual_value'], $row['accumulated_depreciation'], $row['remaining_value'],
            ])->all(), 'Μητρώο'),
            new TableExport(['Κωδικός', 'Πάγιο', 'Απόσβεση περιόδου', 'Αναπόσβεστη αξία μετά την εγγραφή'], $this->data['periodEntries']->map(fn ($entry) => [$entry->asset?->asset_code, $entry->asset?->name, $entry->amount, $entry->book_value])->all(), 'Αποσβέσεις περιόδου'),
            $dimensionSheet($this->data['byCategory'], 'Κατηγορίες'),
            $dimensionSheet($this->data['byDepartment'], 'Τμήματα'),
            $dimensionSheet($this->data['byLocation'], 'Τοποθεσίες'),
            $dimensionSheet($this->data['byFundingSource'], 'Χρηματοδοτήσεις'),
            $dimensionSheet($this->data['byCostCenter'], 'Κέντρα κόστους'),
            $dimensionSheet($this->data['byContract'], 'Συμβάσεις'),
            new TableExport(['Ημερομηνία', 'Κωδικός', 'Πάγιο', 'Τύπος', 'Αναπόσβεστη αξία', 'Έσοδο', 'Κέρδος / Ζημία'], $this->data['disposals']->map(fn (array $row) => [$row['date']?->format('d/m/Y'), $row['code'], $row['name'], $row['type'], $row['book_value'], $row['proceeds'], $row['gain_loss']])->all(), 'Διαγραφές'),
        ];
    }
}
