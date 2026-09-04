<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\ApplicationSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssetQrLabelPdfService
{
    private const LAYOUTS = [
        'small' => [
            'columns' => 3,
            'rows' => 8,
            'cell_height' => 32,
            'qr_size' => 22,
            'font_size' => 6.5,
        ],
        'large' => [
            'columns' => 2,
            'rows' => 5,
            'cell_height' => 50,
            'qr_size' => 32,
            'font_size' => 8.5,
        ],
    ];

    public function download(Collection $assets, array $options): StreamedResponse
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.qr_labels'), 403);

        $layoutName = array_key_exists($options['layout'] ?? '', self::LAYOUTS)
            ? $options['layout']
            : 'small';
        $layout = self::LAYOUTS[$layoutName];
        $perPage = $layout['columns'] * $layout['rows'];
        $startPosition = min(max((int) ($options['start_position'] ?? 1), 1), $perPage);

        $assets->loadMissing(['location', 'department', 'custodian']);
        $assets = $assets->sortBy('asset_code', SORT_NATURAL)->values();
        $settings = ApplicationSetting::current();

        abort_if($assets->isEmpty(), 422, 'Δεν επιλέχθηκαν πάγια για δημιουργία ετικετών.');
        abort_if($assets->count() > 1000, 422, 'Μπορείτε να δημιουργήσετε έως 1.000 ετικέτες κάθε φορά.');

        $labels = $assets->map(fn (Asset $asset): array => [
            'asset' => $asset,
            'qr' => base64_encode((string) QrCode::format('svg')->size(240)->margin(1)->generate($asset->asset_code)),
        ]);

        if ($startPosition > 1) {
            $labels = (new SupportCollection(array_fill(0, $startPosition - 1, null)))
                ->concat($labels)
                ->values();
        }

        $pdf = Pdf::loadView('pdf.asset-qr-labels', [
            'labels' => $labels,
            'layout' => $layout,
            'perPage' => $perPage,
            'settings' => $settings,
            'logoDataUri' => $settings->logoDataUri(),
            'options' => [
                'show_serial' => (bool) ($options['show_serial'] ?? false),
                'show_location' => (bool) ($options['show_location'] ?? false),
                'show_department' => (bool) ($options['show_department'] ?? false),
                'show_custodian' => (bool) ($options['show_custodian'] ?? false),
            ],
        ])->setPaper('a4', 'portrait');

        activity('Ετικέτες QR Παγίων')
            ->event('exported')
            ->causedBy(auth()->user())
            ->withProperties([
                'asset_ids' => $assets->pluck('id')->all(),
                'asset_count' => $assets->count(),
                'layout' => $layoutName,
                'start_position' => $startPosition,
            ])
            ->log('Δημιουργήθηκε αρχείο PDF με ετικέτες QR παγίων.');

        return response()->streamDownload(
            static fn () => print($pdf->output()),
            'etiketes-qr-pagion-' . now()->format('Ymd-His') . '.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
