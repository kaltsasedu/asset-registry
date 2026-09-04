<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TableExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private array $headings, private array $rows, private ?string $title = null) {}

    public function array(): array { return $this->rows; }
    public function headings(): array { return $this->headings; }
    public function title(): string { return $this->title ?? 'Δεδομένα'; }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = max(2, $sheet->getHighestRow());

        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '2F63AA']], 'alignment' => ['horizontal' => 'center', 'vertical' => 'center', 'wrapText' => true]],
            "A1:{$lastColumn}{$lastRow}" => ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8C7D9']]], 'alignment' => ['vertical' => 'top', 'wrapText' => true]],
        ];
    }
}
