<?php

namespace App\Exports;

use App\Models\Asset;
use App\Models\AssetStatusTransaction;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithColumnWidths
{
    public function query(): Builder
    {
        return Asset::query()->with(['category', 'supplier', 'procurementContract', 'fundingSource', 'costCenter', 'location', 'department']);
    }

    public function headings(): array
    {
        return ['Κωδικός', 'Πάγιο', 'Κατηγορία', 'Προμηθευτής', 'Σύμβαση', 'Πηγή Χρηματοδότησης', 'Κέντρο Κόστους', 'Τοποθεσία', 'Τμήμα', 'Σειριακός Αριθμός', 'Κατασκευαστής', 'Μοντέλο', 'Ημ. Απόκτησης', 'Έναρξη Απόσβεσης', 'Αξία Κτήσης', 'ΦΠΑ', 'Υπολειμματική Αξία', 'Συντελεστής Απόσβεσης', 'Χρήσιμη Ζωή', 'Συσσωρευμένες Αποσβέσεις', 'Ετήσια Απόσβεση', 'Αναπόσβεστη Αξία', 'Αρ. Τιμολογίου', 'Ημ. Τιμολογίου', 'Κατάσταση'];
    }

    public function map($asset): array
    {
        return [$asset->asset_code, $asset->name, $asset->category?->name, $asset->supplier?->name, $asset->procurementContract?->contract_number, $asset->fundingSource?->name, $asset->costCenter?->name, $asset->location?->name, $asset->department?->name, $asset->serial_number, $asset->manufacturer, $asset->model, optional($asset->purchase_date)->format('d/m/Y'), optional($asset->depreciation_start_date)->format('d/m/Y'), $asset->purchase_value, $asset->vat_amount, $asset->residual_value, $asset->depreciation_rate, $asset->useful_life_years, $asset->accumulated_depreciation, $asset->annual_depreciation, $asset->remaining_value, $asset->invoice_number, optional($asset->invoice_date)->format('d/m/Y'), AssetStatusTransaction::STATUSES[$asset->status] ?? $asset->status];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = max(2, $sheet->getHighestRow());
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2F63AA']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
            "A1:Y{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8C7D9']]], 'alignment' => ['vertical' => 'top', 'wrapText' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return ['A' => 14, 'B' => 28, 'C' => 35, 'D' => 22, 'E' => 20, 'F' => 28, 'G' => 24, 'H' => 24, 'I' => 20, 'J' => 20, 'K' => 20, 'L' => 18, 'M' => 18, 'N' => 18, 'O' => 14, 'P' => 14, 'Q' => 14, 'R' => 14, 'S' => 14, 'T' => 16, 'U' => 16, 'V' => 16, 'W' => 16, 'X' => 14, 'Y' => 14];
    }
}
