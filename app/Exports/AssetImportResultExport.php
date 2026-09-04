<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssetImportResultExport implements Export, WithMultipleSheets
{
    public function __construct(private array $result) {}

    public function sheets(): array
    {
        return [
            new TableExport(['Στοιχείο', 'Πλήθος'], [
                ['Σύνολο γραμμών', $this->result['total']],
                ['Νέα πάγια', $this->result['created']],
                ['Ενημερωμένα πάγια', $this->result['updated']],
                ['Αποτυχημένες γραμμές', count($this->result['failed'])],
            ], 'Σύνοψη'),
            new TableExport(['Γραμμή', 'Ενέργεια', 'Κωδικός παγίου', 'Περιγραφή'], collect($this->result['successful'])->map(fn (array $row): array => [$row['row_number'], $row['action'], $row['asset_code'], $row['name']])->all(), 'Επιτυχείς'),
            new TableExport(['Γραμμή', 'Σφάλματα'], collect($this->result['failed'])->map(fn (array $row): array => [$row['row_number'], implode(' · ', $row['errors'])])->all(), 'Σφάλματα'),
        ];
    }
}
