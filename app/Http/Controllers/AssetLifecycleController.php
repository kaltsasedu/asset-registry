<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\AssetDataScope;
use Illuminate\View\View;

class AssetLifecycleController extends Controller
{
    public function show(Asset $asset): View
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.view'), 403);
        abort_unless(app(AssetDataScope::class)->canAccess($asset, auth()->user()), 404);
        $asset->load(['statusTransactions.creator']);

        return view('assets.lifecycle-history', compact('asset'));
    }
}
