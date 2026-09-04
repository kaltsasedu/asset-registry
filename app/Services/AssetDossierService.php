<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class AssetDossierService
{
    public function pdf(Asset $asset)
    {
        $asset = $this->loadAsset($asset);
        $settings = ApplicationSetting::current();

        return Pdf::loadView('pdf.asset-dossier', [
            'asset' => $asset,
            'settings' => $settings,
            'logoDataUri' => $settings->logoDataUri(),
            'qrCode' => base64_encode((string) QrCode::format('svg')->size(220)->margin(1)->generate($asset->asset_code)),
        ])->setPaper('a4', 'portrait');
    }

    public function zip(Asset $asset): BinaryFileResponse
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Η επέκταση PHP ZipArchive δεν είναι ενεργή. Ενεργοποιήστε την επέκταση zip στο php.ini του Laragon.');
        }

        $asset = $this->loadAsset($asset);
        $directory = storage_path('app/private/asset-dossiers');
        File::ensureDirectoryExists($directory);

        $baseName = 'fakelos-pagiou-' . $this->safeDownloadName($asset->asset_code, $asset->id);
        $zipPath = $directory . DIRECTORY_SEPARATOR . $baseName . '-' . now()->format('Ymd-His-u') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Δεν ήταν δυνατή η δημιουργία του ηλεκτρονικού φακέλου παγίου.');
        }

        $pdfName = $baseName . '.pdf';
        $zip->addFromString($pdfName, $this->pdf($asset)->output());

        $usedArchiveNames = [$pdfName => true];
        $addedSourcePaths = [];
        $contents = [
            'ΗΛΕΚΤΡΟΝΙΚΟΣ ΦΑΚΕΛΟΣ ΠΑΓΙΟΥ',
            'Κωδικός: ' . $asset->asset_code,
            'Περιγραφή: ' . $asset->name,
            'Δημιουργήθηκε: ' . now()->format('d/m/Y H:i:s'),
            '',
            'ΠΕΡΙΕΧΟΜΕΝΑ',
            '- ' . $pdfName,
        ];

        foreach ($this->attachments($asset) as $attachment) {
            $sourcePath = $attachment['path'];

            if (! is_string($sourcePath) || $sourcePath === '' || isset($addedSourcePaths[$sourcePath])) {
                continue;
            }

            $addedSourcePaths[$sourcePath] = true;

            if (! Storage::disk('local')->exists($sourcePath)) {
                $contents[] = '- ΔΕΝ ΒΡΕΘΗΚΕ: ' . $attachment['label'] . ' (' . basename($sourcePath) . ')';
                continue;
            }

            $archiveName = $this->uniqueArchiveName(
                $attachment['folder'] . '/' . $this->safeName(basename($sourcePath)),
                $usedArchiveNames,
            );

            if ($zip->addFile(Storage::disk('local')->path($sourcePath), $archiveName)) {
                $contents[] = '- ' . $archiveName . ' — ' . $attachment['label'];
            }
        }

        $zip->addFromString('ΠΕΡΙΕΧΟΜΕΝΑ.txt', implode("\r\n", $contents) . "\r\n");
        if (! $zip->close()) {
            File::delete($zipPath);
            throw new RuntimeException('Δεν ήταν δυνατή η ολοκλήρωση του ηλεκτρονικού φακέλου παγίου.');
        }

        return response()
            ->download($zipPath, basename($zipPath), ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function loadAsset(Asset $asset): Asset
    {
        return $asset->loadMissing([
            'category', 'supplier', 'procurementContract', 'fundingSource', 'costCenter',
            'location', 'department', 'custodian.user', 'depreciationBook', 'receiptLine.receipt',
            'movements.fromLocation', 'movements.toLocation', 'movements.fromDepartment',
            'movements.toDepartment', 'movements.fromCustodian', 'movements.toCustodian',
            'movements.approver', 'movements.deliveryConfirmer', 'movements.receiptConfirmer',
            'statusTransactions.creator', 'documents.creator', 'depreciationProfiles.book',
            'depreciationEntries.book', 'depreciationEntries.period', 'inventoryEntries.cycle',
            'inventoryEntries.verifier', 'inventoryEntries.actualLocation', 'maintenances.assignee',
            'insurances', 'activities.causer',
        ]);
    }

    /** @return array<int, array{folder: string, label: string, path: mixed}> */
    private function attachments(Asset $asset): array
    {
        $attachments = [];

        foreach ($asset->documents as $document) {
            $attachments[] = [
                'folder' => 'Δικαιολογητικά',
                'label' => $document->title,
                'path' => $document->file_path,
            ];
        }

        foreach ($asset->statusTransactions as $transaction) {
            $attachments[] = [
                'folder' => 'Κύκλος Ζωής',
                'label' => $transaction->document_number ?: 'Έγγραφο συναλλαγής',
                'path' => $transaction->document_path,
            ];
        }

        foreach ($asset->insurances as $insurance) {
            $attachments[] = [
                'folder' => 'Ασφάλιση',
                'label' => 'Ασφαλιστήριο ' . $insurance->policy_number,
                'path' => $insurance->document_path,
            ];
        }

        if ($asset->procurementContract?->document_path) {
            $attachments[] = [
                'folder' => 'Σύμβαση',
                'label' => 'Σύμβαση ' . $asset->procurementContract->contract_number,
                'path' => $asset->procurementContract->document_path,
            ];
        }

        $receipt = $asset->receiptLine?->receipt;

        if ($receipt) {
            foreach ([
                'invoice_file_path' => 'Τιμολόγιο παραλαβής',
                'acceptance_protocol_file_path' => 'Πρωτόκολλο παραλαβής',
                'contract_file_path' => 'Σύμβαση παραλαβής',
            ] as $field => $label) {
                $attachments[] = [
                    'folder' => 'Παραλαβή',
                    'label' => $label,
                    'path' => $receipt->{$field},
                ];
            }
        }

        return $attachments;
    }

    private function safeName(string $value): string
    {
        $value = preg_replace('/[^\pL\pN._-]+/u', '-', trim($value)) ?: 'arxeio';

        return trim($value, '.-_') ?: 'arxeio';
    }

    private function safeDownloadName(string $value, int $fallbackId): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?: '';

        return trim($value, '.-_') ?: (string) $fallbackId;
    }

    /** @param array<string, bool> $used */
    private function uniqueArchiveName(string $name, array &$used): string
    {
        $candidate = $name;
        $directory = pathinfo($name, PATHINFO_DIRNAME);
        $filename = pathinfo($name, PATHINFO_FILENAME);
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $counter = 2;

        while (isset($used[$candidate])) {
            $candidate = ($directory === '.' ? '' : $directory . '/')
                . $filename . '-' . $counter
                . ($extension === '' ? '' : '.' . $extension);
            $counter++;
        }

        $used[$candidate] = true;

        return $candidate;
    }
}
