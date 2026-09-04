<?php

namespace App\Filament\Pages;

use App\Models\Asset;
use App\Models\AssetMovement;
use App\Models\AssetReceipt;
use App\Models\DepreciationPeriod;
use App\Models\ProcurementContract;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DataQualityChecks extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected string $view = 'filament.pages.data-quality-checks';

    public static function getNavigationLabel(): string { return 'Έλεγχοι Ποιότητας'; }
    public function getTitle(): string { return 'Έλεγχοι Ποιότητας'; }
    public static function getNavigationGroup(): ?string { return 'Ρυθμίσεις'; }
    public static function getNavigationSort(): ?int { return 5; }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.view');
    }

    protected function getViewData(): array
    {
        $duplicateSerials = Asset::query()->select('serial_number', DB::raw('COUNT(*) as total'))->whereNotNull('serial_number')->where('serial_number', '!=', '')->groupBy('serial_number')->having('total', '>', 1)->get();
        $duplicateInvoices = AssetReceipt::query()->with('supplier')->select('supplier_id', 'invoice_number', DB::raw('COUNT(*) as total'))->whereNotNull('invoice_number')->where('invoice_number', '!=', '')->groupBy('supplier_id', 'invoice_number')->having('total', '>', 1)->get();
        $missing = Asset::with(['category', 'location', 'custodian'])->where(function ($q): void { $q->whereNull('asset_category_id')->orWhereNull('location_id')->orWhereNull('asset_custodian_id'); })->orderBy('asset_code')->get();
        $rateMismatches = Asset::query()->whereNotNull('useful_life_years')->where('useful_life_years', '>', 0)->whereRaw('ABS(depreciation_rate - (100 / useful_life_years)) > 0.05')->orderBy('asset_code')->get();
        $valueAnomalies = Asset::query()->where(function ($q): void { $q->where('remaining_value', '<', 0)->orWhereColumn('remaining_value', '>', 'purchase_value')->orWhereColumn('remaining_value', '<', 'residual_value')->orWhere('accumulated_depreciation', '<', 0)->orWhereColumn('accumulated_depreciation', '>', 'purchase_value'); })->orderBy('asset_code')->get();
        $movementIssues = AssetMovement::with(['asset', 'toCustodian'])->where(function ($q): void { $q->whereNull('to_custodian_id')->orWhere('status', '!=', 'completed'); })->orderByDesc('movement_date')->get();
        $lastLocked = DepreciationPeriod::where('status', 'locked')->latest('posted_at')->first();
        $lockedChanges = $lastLocked ? Activity::with(['subject', 'causer'])->where('subject_type', Asset::class)->whereIn('subject_id', Asset::query()->select('id'))->where('created_at', '>', $lastLocked->posted_at ?? $lastLocked->updated_at)->latest()->limit(100)->get() : collect();
        $contractIssues = ProcurementContract::with(['supplier', 'fundingSource', 'costCenter'])->get()->filter(fn (ProcurementContract $contract): bool => $contract->remaining_amount < -0.005 || ! $contract->funding_source_id || ! $contract->cost_center_id)->values();

        return compact('duplicateSerials', 'duplicateInvoices', 'missing', 'rateMismatches', 'valueAnomalies', 'movementIssues', 'lastLocked', 'lockedChanges', 'contractIssues');
    }
}
