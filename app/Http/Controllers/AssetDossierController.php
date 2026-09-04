<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetDossierService;
use App\Services\AssetDataScope;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetDossierController extends Controller
{
    public function pdf(Asset $asset, AssetDossierService $service): StreamedResponse
    {
        $this->authorizeExport();
        abort_unless(app(AssetDataScope::class)->canAccess($asset, auth()->user()), 404);
        $contents = $service->pdf($asset)->output();
        $this->logExport($asset, 'pdf');

        return response()->streamDownload(
            static fn () => print($contents),
            'fakelos-pagiou-' . $this->safeName($asset->asset_code, $asset->id) . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function zip(Asset $asset, AssetDossierService $service): BinaryFileResponse
    {
        $this->authorizeExport();
        abort_unless(app(AssetDataScope::class)->canAccess($asset, auth()->user()), 404);
        $response = $service->zip($asset);
        $this->logExport($asset, 'zip');

        return $response;
    }

    private function authorizeExport(): void
    {
        abort_unless(
            auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.dossier_export'),
            403,
        );
    }

    private function logExport(Asset $asset, string $format): void
    {
        activity('Ηλεκτρονικοί Φάκελοι Παγίων')
            ->event('exported')
            ->performedOn($asset)
            ->causedBy(auth()->user())
            ->withProperties(['format' => $format, 'asset_code' => $asset->asset_code])
            ->log('Εξήχθη ηλεκτρονικός φάκελος παγίου σε μορφή ' . strtoupper($format) . '.');
    }

    private function safeName(string $value, int $fallbackId): string
    {
        $value = preg_replace('/[^A-Za-z0-9._-]+/', '-', $value) ?: '';

        return trim($value, '.-_') ?: (string) $fallbackId;
    }
}
