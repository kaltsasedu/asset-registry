<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetDepreciationProfile;
use App\Models\AssetDocument;
use App\Models\AssetReceipt;
use App\Models\AssetStatusTransaction;
use App\Models\ProcurementContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetReceiptCapitalizationService
{
    public function capitalize(AssetReceipt $receipt): int
    {
        if ($receipt->status === 'capitalized') {
            throw ValidationException::withMessages(['receipt' => 'Η παραλαβή έχει ήδη κεφαλαιοποιηθεί.']);
        }

        $receipt->loadMissing('lines');
        if ($receipt->lines->isEmpty()) {
            throw ValidationException::withMessages(['lines' => 'Προσθέστε τουλάχιστον μία γραμμή παραλαβής πριν από την κεφαλαιοποίηση.']);
        }

        return DB::transaction(function () use ($receipt): int {
            $receipt = AssetReceipt::query()->lockForUpdate()->findOrFail($receipt->id);
            $receipt->setRelation('lines', $receipt->lines()->lockForUpdate()->get());
            if ($receipt->status === 'capitalized') {
                throw ValidationException::withMessages(['receipt' => 'Η παραλαβή έχει ήδη κεφαλαιοποιηθεί.']);
            }
            if ($receipt->lines->isEmpty()) {
                throw ValidationException::withMessages(['lines' => 'Η παραλαβή δεν έχει γραμμές προς κεφαλαιοποίηση.']);
            }

            if ($receipt->procurement_contract_id) {
                $contract = ProcurementContract::query()->lockForUpdate()->findOrFail($receipt->procurement_contract_id);
                if (in_array($contract->status, ['draft', 'terminated'], true)) {
                    throw ValidationException::withMessages(['procurement_contract_id' => 'Η επιλεγμένη σύμβαση δεν είναι ενεργή για κεφαλαιοποίηση.']);
                }
                if ((int) $contract->supplier_id !== (int) $receipt->supplier_id) {
                    throw ValidationException::withMessages(['supplier_id' => 'Ο προμηθευτής της παραλαβής δεν συμφωνεί με τον προμηθευτή της σύμβασης.']);
                }
                if ($receipt->total_amount > $contract->remaining_amount + 0.005) {
                    throw ValidationException::withMessages(['receipt' => 'Η παραλαβή υπερβαίνει το διαθέσιμο υπόλοιπο της σύμβασης κατά € ' . number_format($receipt->total_amount - $contract->remaining_amount, 2, ',', '.') . '.']);
                }
            }

            $created = 0;
            foreach ($receipt->lines as $line) {
                for ($index = 1; $index <= $line->quantity; $index++) {
                    $asset = Asset::create([
                        'name' => $line->quantity > 1 ? $line->description . ' (' . $index . '/' . $line->quantity . ')' : $line->description,
                        'description' => $line->notes,
                        'asset_category_id' => $line->asset_category_id,
                        'asset_receipt_line_id' => $line->id,
                        'depreciation_book_id' => $line->depreciation_book_id,
                        'supplier_id' => $receipt->supplier_id,
                        'procurement_contract_id' => $receipt->procurement_contract_id,
                        'funding_source_id' => $receipt->funding_source_id,
                        'cost_center_id' => $receipt->cost_center_id,
                        'location_id' => $line->location_id,
                        'department_id' => $line->department_id,
                        'asset_custodian_id' => $line->asset_custodian_id,
                        'serial_number' => $line->quantity === 1 ? $line->serial_number : null,
                        'manufacturer' => $line->manufacturer,
                        'model' => $line->model,
                        'purchase_date' => $receipt->receipt_date,
                        'depreciation_start_date' => $line->depreciation_start_date ?? $receipt->receipt_date,
                        'purchase_value' => $line->unit_value,
                        'vat_amount' => $line->vat_amount,
                        'residual_value' => $line->residual_value,
                        'depreciation_rate' => $line->depreciation_rate,
                        'depreciation_method' => $line->depreciation_method,
                        'depreciation_timing' => $line->depreciation_timing,
                        'useful_life_years' => $line->useful_life_years,
                        'accumulated_depreciation' => 0,
                        'book_value' => $line->unit_value,
                        'invoice_number' => $receipt->invoice_number,
                        'invoice_date' => $receipt->invoice_date,
                        'status' => 'in_use',
                    ]);

                    AssetDepreciationProfile::create([
                        'asset_id' => $asset->id, 'depreciation_book_id' => $asset->depreciation_book_id,
                        'depreciation_start_date' => $asset->depreciation_start_date, 'residual_value' => $asset->residual_value,
                        'depreciation_rate' => $asset->depreciation_rate, 'depreciation_method' => $asset->depreciation_method,
                        'depreciation_timing' => $asset->depreciation_timing, 'useful_life_years' => $asset->useful_life_years,
                        'accumulated_depreciation' => 0, 'remaining_value' => $asset->remaining_value,
                    ]);

                    AssetStatusTransaction::create([
                        'asset_id' => $asset->id, 'transaction_type' => 'initial_registration', 'to_status' => 'in_use',
                        'reason' => 'Κεφαλαιοποίηση από παραλαβή ' . $receipt->receipt_number . '.',
                        'document_number' => $receipt->acceptance_protocol_number ?? $receipt->invoice_number,
                        'document_date' => $receipt->acceptance_protocol_date ?? $receipt->receipt_date,
                        'purchase_value' => $asset->purchase_value, 'accumulated_depreciation' => 0, 'book_value' => $asset->remaining_value,
                        'created_by' => auth()->id(),
                    ]);

                    $this->copyDocuments($receipt, $asset);
                    $created++;
                }
            }

            $receipt->update(['status' => 'capitalized', 'capitalized_at' => now(), 'capitalized_by' => auth()->id()]);
            return $created;
        });
    }

    private function copyDocuments(AssetReceipt $receipt, Asset $asset): void
    {
        foreach ([
            [$receipt->invoice_file_path, 'invoice', 'Τιμολόγιο ' . ($receipt->invoice_number ?? '')],
            [$receipt->acceptance_protocol_file_path, 'acceptance_protocol', 'Πρωτόκολλο παραλαβής ' . ($receipt->acceptance_protocol_number ?? '')],
            [$receipt->contract_file_path ?? $receipt->procurementContract?->document_path, 'contract', 'Σύμβαση ' . ($receipt->contract_number ?? '')],
        ] as [$filePath, $type, $title]) {
            if (filled($filePath)) {
                AssetDocument::create(['asset_id' => $asset->id, 'document_type' => $type, 'title' => trim($title), 'document_number' => $type === 'contract' ? $receipt->contract_number : ($type === 'invoice' ? $receipt->invoice_number : $receipt->acceptance_protocol_number), 'document_date' => $type === 'contract' ? $receipt->contract_date : ($type === 'invoice' ? $receipt->invoice_date : $receipt->acceptance_protocol_date), 'file_path' => $filePath, 'created_by' => auth()->id()]);
            }
        }
    }
}
