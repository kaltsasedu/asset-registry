<?php

namespace App\Http\Controllers;

use App\Exports\FinancialReportExport;
use App\Models\AssetDepreciationProfile;
use App\Models\AssetStatusTransaction;
use App\Models\DepreciationBook;
use App\Models\DepreciationEntry;
use App\Models\FundingSource;
use App\Models\CostCenter;
use App\Models\ProcurementContract;
use App\Models\ApplicationSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class DepreciationReportController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('depreciation-periods.view'), 403);

        $data = $this->reportData($request);

        return match ($request->string('format')->toString()) {
            'excel' => $this->excel($data),
            'pdf' => $this->pdf($data),
            default => view('depreciation.reports', $data),
        };
    }

    private function reportData(Request $request): array
    {
        $asOf = Carbon::parse($request->input('as_of', now()->toDateString()))->endOfDay();
        $year = (int) $request->input('year', now()->year);
        $month = min(12, max(1, (int) $request->input('month', now()->month)));
        $books = DepreciationBook::query()->where('is_active', true)->orderBy('name')->get();
        $bookId = (int) $request->input('book_id', $books->firstWhere('is_default', true)?->id ?? $books->first()?->id);
        $book = $books->firstWhere('id', $bookId);
        $fundingSources = FundingSource::query()->orderBy('name')->get();
        $costCenters = CostCenter::query()->orderBy('name')->get();
        $contracts = ProcurementContract::query()->orderBy('contract_number')->get();
        $fundingSourceId = (int) $request->input('funding_source_id', 0);
        $costCenterId = (int) $request->input('cost_center_id', 0);
        $contractId = (int) $request->input('contract_id', 0);

        $profiles = AssetDepreciationProfile::query()
            ->with(['asset.category', 'asset.department', 'asset.location', 'asset.fundingSource', 'asset.costCenter', 'asset.procurementContract', 'book'])
            ->where('depreciation_book_id', $bookId)
            ->whereHas('asset', fn ($query) => $query->whereDate('purchase_date', '<=', $asOf))
            ->when($fundingSourceId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('funding_source_id', $fundingSourceId)))
            ->when($costCenterId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('cost_center_id', $costCenterId)))
            ->when($contractId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('procurement_contract_id', $contractId)))
            ->orderBy('asset_id')
            ->get();

        $amountsByProfile = DepreciationEntry::query()
            ->selectRaw('asset_depreciation_profile_id, SUM(amount) as accumulated')
            ->whereIn('asset_depreciation_profile_id', $profiles->pluck('id'))
            ->where('status', 'posted')
            ->where('posted_at', '<=', $asOf)
            ->groupBy('asset_depreciation_profile_id')
            ->pluck('accumulated', 'asset_depreciation_profile_id');

        $registerRows = $profiles->map(function (AssetDepreciationProfile $profile) use ($amountsByProfile): array {
            $asset = $profile->asset;
            $purchaseValue = (float) $asset->purchase_value;
            $residualValue = (float) $profile->residual_value;
            $accumulated = min(max(0, (float) ($amountsByProfile[$profile->id] ?? 0)), max(0, $purchaseValue - $residualValue));

            return [
                'asset_id' => $asset->id,
                'code' => $asset->asset_code,
                'name' => $asset->name,
                'category' => $asset->category?->name ?? 'Χωρίς κατηγορία',
                'department' => $asset->department?->name ?? 'Χωρίς τμήμα',
                'location' => $asset->location?->name ?? 'Χωρίς τοποθεσία',
                'funding_source' => $asset->fundingSource?->name ?? 'Χωρίς χρηματοδότηση',
                'cost_center' => $asset->costCenter?->name ?? 'Χωρίς κέντρο κόστους',
                'contract' => $asset->procurementContract?->contract_number ?? 'Χωρίς σύμβαση',
                'purchase_value' => $purchaseValue,
                'residual_value' => $residualValue,
                'accumulated_depreciation' => $accumulated,
                'remaining_value' => max($residualValue, $purchaseValue - $accumulated),
                'annual_depreciation' => (float) $profile->annual_depreciation,
                'status' => $asset->status,
            ];
        })->values();

        $periodEntries = DepreciationEntry::query()
            ->with('asset')
            ->where('depreciation_book_id', $bookId)
            ->where('year', $year)
            ->where('month', $month)
            ->where('status', 'posted')
            ->when($fundingSourceId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('funding_source_id', $fundingSourceId)))
            ->when($costCenterId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('cost_center_id', $costCenterId)))
            ->when($contractId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('procurement_contract_id', $contractId)))
            ->orderBy('asset_id')
            ->get();

        $fullyDepreciated = $registerRows->filter(fn (array $row): bool => $row['remaining_value'] <= $row['residual_value'] + 0.01)->values();
        $nearFull = $registerRows->filter(fn (array $row): bool => $row['remaining_value'] > $row['residual_value'] + 0.01 && $row['remaining_value'] <= ($row['annual_depreciation'] / 12) + $row['residual_value'] + 0.01)->values();

        $disposals = AssetStatusTransaction::query()
            ->with('asset')
            ->whereIn('transaction_type', ['retirement', 'sale', 'destruction', 'donation'])
            ->whereDate('document_date', '<=', $asOf)
            ->whereHas('asset.depreciationProfiles', fn ($query) => $query->where('depreciation_book_id', $bookId))
            ->when($fundingSourceId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('funding_source_id', $fundingSourceId)))
            ->when($costCenterId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('cost_center_id', $costCenterId)))
            ->when($contractId, fn ($query) => $query->whereHas('asset', fn ($assetQuery) => $assetQuery->where('procurement_contract_id', $contractId)))
            ->orderByDesc('document_date')
            ->get()
            ->map(function (AssetStatusTransaction $transaction) use ($registerRows): array {
                $row = $registerRows->firstWhere('asset_id', $transaction->asset_id);
                $bookValue = (float) ($row['remaining_value'] ?? $transaction->book_value ?? 0);
                $proceeds = (float) $transaction->disposal_proceeds;

                return [
                    'date' => $transaction->document_date,
                    'code' => $transaction->asset?->asset_code,
                    'name' => $transaction->asset?->name,
                    'type' => AssetStatusTransaction::TYPES[$transaction->transaction_type] ?? $transaction->transaction_type,
                    'book_value' => $bookValue,
                    'proceeds' => $proceeds,
                    'gain_loss' => $proceeds - $bookValue,
                ];
            });

        return [
            'asOf' => $asOf,
            'year' => $year,
            'month' => $month,
            'books' => $books,
            'bookId' => $bookId,
            'book' => $book,
            'fundingSources' => $fundingSources,
            'costCenters' => $costCenters,
            'contracts' => $contracts,
            'fundingSourceId' => $fundingSourceId,
            'costCenterId' => $costCenterId,
            'contractId' => $contractId,
            'registerRows' => $registerRows,
            'periodEntries' => $periodEntries,
            'fullyDepreciated' => $fullyDepreciated,
            'nearFull' => $nearFull,
            'byCategory' => $this->dimensionRows($registerRows, 'category'),
            'byDepartment' => $this->dimensionRows($registerRows, 'department'),
            'byLocation' => $this->dimensionRows($registerRows, 'location'),
            'byFundingSource' => $this->dimensionRows($registerRows, 'funding_source'),
            'byCostCenter' => $this->dimensionRows($registerRows, 'cost_center'),
            'byContract' => $this->dimensionRows($registerRows, 'contract'),
            'disposals' => $disposals,
        ];
    }

    private function dimensionRows(Collection $rows, string $field): Collection
    {
        return $rows->groupBy($field)->map(function (Collection $items, string $label): array {
            return [
                'label' => $label,
                'assets' => $items->count(),
                'purchase_value' => $items->sum('purchase_value'),
                'accumulated_depreciation' => $items->sum('accumulated_depreciation'),
                'remaining_value' => $items->sum('remaining_value'),
            ];
        })->sortByDesc('remaining_value')->values();
    }

    private function excel(array $data)
    {
        return Excel::download(new FinancialReportExport($data), 'oikonomiko-mitroo-' . ($data['book']?->code ?? 'vivlio') . '-' . $data['asOf']->format('Ymd') . '.xlsx');
    }

    private function pdf(array $data)
    {
        $settings = ApplicationSetting::current();

        return Pdf::loadView('pdf.depreciation-financial-report', array_merge($data, [
            'settings' => $settings,
            'logoDataUri' => $settings->logoDataUri(),
        ]))
            ->setPaper('a4', 'landscape')
            ->download('oikonomiko-mitroo-' . ($data['book']?->code ?? 'vivlio') . '-' . $data['asOf']->format('Ymd') . '.pdf');
    }
}
