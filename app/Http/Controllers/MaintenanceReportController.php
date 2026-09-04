<?php
namespace App\Http\Controllers;

use App\Models\AssetMaintenance;
use Illuminate\Support\Facades\DB;

class MaintenanceReportController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-maintenances.view'), 403);

        $byAsset = AssetMaintenance::with('asset')->select('asset_id', DB::raw('SUM(cost) total_cost'), DB::raw('COUNT(*) jobs'))->groupBy('asset_id')->orderByDesc('total_cost')->get();
        $byCategory = AssetMaintenance::query()->join('assets', 'asset_maintenances.asset_id', '=', 'assets.id')->leftJoin('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')->selectRaw("COALESCE(asset_categories.name, 'Χωρίς κατηγορία') label, SUM(asset_maintenances.cost) total_cost, COUNT(*) jobs")->groupBy('label')->orderByDesc('total_cost')->get();

        return view('maintenance.reports', compact('byAsset', 'byCategory'));
    }
}
