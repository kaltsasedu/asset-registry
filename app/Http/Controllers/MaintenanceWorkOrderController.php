<?php

namespace App\Http\Controllers;

use App\Models\AssetMaintenance;
use App\Models\ApplicationSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class MaintenanceWorkOrderController extends Controller
{
    public function show(AssetMaintenance $maintenance)
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-maintenances.view'), 403);
        abort_unless($maintenance->asset()->exists(), 404);

        $maintenance->load(['asset.category', 'asset.location', 'asset.department', 'assignee']);
        $settings = ApplicationSetting::current();
        $logoDataUri = $settings->logoDataUri();

        return Pdf::loadView('pdf.maintenance-work-order', compact('maintenance', 'settings', 'logoDataUri'))
            ->setPaper('a4')
            ->stream('deltio-syntirisis-' . $maintenance->id . '.pdf');
    }
}
