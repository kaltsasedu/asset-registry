<?php
namespace App\Http\Controllers;
use App\Models\Asset; use App\Services\AssetDataScope; use SimpleSoftwareIO\QrCode\Facades\QrCode;
class AssetQrCodeController extends Controller { public function show(Asset $asset) { abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.view'), 403); abort_unless(app(AssetDataScope::class)->canAccess($asset, auth()->user()), 404); return response(QrCode::format('svg')->size(260)->generate($asset->asset_code))->header('Content-Type','image/svg+xml')->header('Content-Disposition', 'inline; filename="qr-'.$asset->asset_code.'.svg"'); } }
