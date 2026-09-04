<?php

namespace App\Http\Controllers;

use App\Models\AssetMovement;
use App\Models\ApplicationSetting;
use Barryvdh\DomPDF\Facade\Pdf;

class AssetMovementProtocolController extends Controller
{
    public function show(AssetMovement $movement)
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-movements.view'), 403);
        abort_unless($movement->asset()->exists(), 404);
        $movement->load(['asset', 'fromLocation', 'toLocation', 'fromDepartment', 'toDepartment', 'fromCustodian', 'toCustodian', 'approver']);
        $settings = ApplicationSetting::current();
        $logoDataUri = $settings->logoDataUri();
        return Pdf::loadView('pdf.asset-movement-protocol', compact('movement', 'settings', 'logoDataUri'))
            ->setPaper('a4')
            ->stream('protokollo-diakinisis-' . $movement->id . '.pdf');
    }
}
