<?php

namespace App\Http\Controllers;

use App\Models\AssetDocument;
use App\Models\AssetStatusTransaction;
use App\Models\AssetInsurance;
use App\Models\ProcurementContract;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetDocumentDownloadController extends Controller
{
    public function document(AssetDocument $document): StreamedResponse
    {
        abort_unless($this->allowed('asset-documents.view'), 403);
        abort_unless($document->asset()->exists(), 404);
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, basename($document->file_path));
    }

    public function transaction(AssetStatusTransaction $transaction): StreamedResponse
    {
        abort_unless($this->allowed('assets.view'), 403);
        abort_unless($transaction->asset()->exists(), 404);
        abort_unless($transaction->document_path && Storage::disk('local')->exists($transaction->document_path), 404);

        return Storage::disk('local')->download($transaction->document_path, basename($transaction->document_path));
    }

    public function insurance(AssetInsurance $insurance): StreamedResponse
    {
        abort_unless($this->allowed('asset-insurances.view'), 403);
        abort_unless($insurance->asset()->exists(), 404);
        abort_unless($insurance->document_path && Storage::disk('local')->exists($insurance->document_path), 404);

        return Storage::disk('local')->download($insurance->document_path, basename($insurance->document_path));
    }

    public function procurementContract(ProcurementContract $contract): StreamedResponse
    {
        abort_unless($this->allowed('procurement-contracts.view'), 403);
        abort_unless($contract->document_path && Storage::disk('local')->exists($contract->document_path), 404);

        return Storage::disk('local')->download($contract->document_path, basename($contract->document_path));
    }

    private function allowed(string $permission): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can($permission);
    }
}
