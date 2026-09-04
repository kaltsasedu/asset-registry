<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\ApplicationSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetRegisterController extends Controller
{
    public function pdf()
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.export'), 403);
        $assets = Asset::with(['category', 'supplier', 'procurementContract', 'fundingSource', 'costCenter', 'location', 'department'])->orderBy('asset_code')->get();
        $settings = ApplicationSetting::current();
        $logoDataUri = $settings->logoDataUri();

        return Pdf::loadView('pdf.asset-register', compact('assets', 'settings', 'logoDataUri'))
            ->setPaper('a4', 'landscape')
            ->download('mitroo-pagion.pdf');
    }
}
