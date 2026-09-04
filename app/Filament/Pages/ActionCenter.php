<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AssetInsurances\AssetInsuranceResource;
use App\Filament\Resources\AssetMaintenances\AssetMaintenanceResource;
use App\Filament\Resources\AssetMovements\AssetMovementResource;
use App\Filament\Resources\AssetReceipts\AssetReceiptResource;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\DepreciationPeriods\DepreciationPeriodResource;
use App\Filament\Resources\InventoryCycles\InventoryCycleResource;
use App\Filament\Resources\SystemBackups\SystemBackupResource;
use App\Models\Asset;
use App\Models\ApplicationSetting;
use App\Models\AssetInsurance;
use App\Models\AssetMaintenance;
use App\Models\AssetMovement;
use App\Models\AssetReceipt;
use App\Models\DepreciationPeriod;
use App\Models\InventoryCycle;
use App\Models\SystemBackup;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActionCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    protected string $view = 'filament.pages.action-center';

    public static function getNavigationLabel(): string { return 'Κέντρο Ενεργειών'; }
    public function getTitle(): string { return 'Κέντρο Ενεργειών και Εκκρεμοτήτων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 10; }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('action-center.view');
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $settings = ApplicationSetting::current();
        $maintenanceDeadline = $today->copy()->addDays($settings->maintenance_warning_days);
        $warrantyDeadline = $today->copy()->addDays($settings->warranty_warning_days);
        $insuranceDeadline = $today->copy()->addDays($settings->insurance_warning_days);
        $sections = [];

        if ($this->allowed('asset-movements.view')) {
            $query = AssetMovement::query()
                ->with('asset')
                ->whereIn('status', ['pending_approval', 'approved']);
            $count = (clone $query)->count();
            $critical = (clone $query)->where('status', 'pending_approval')->count();
            $items = $query->orderBy('movement_date')->limit(8)->get()->map(function (AssetMovement $movement): array {
                $status = $movement->status === 'pending_approval' ? 'Προς έγκριση' : 'Αναμένει ολοκλήρωση';

                return [
                    'title' => ($movement->asset?->asset_code ?? 'Χωρίς κωδικό') . ' — ' . ($movement->asset?->name ?? 'Άγνωστο πάγιο'),
                    'subtitle' => 'Ημερομηνία διακίνησης: ' . ($movement->movement_date?->format('d/m/Y') ?? '—'),
                    'badge' => $status,
                    'color' => $movement->status === 'pending_approval' ? 'danger' : 'warning',
                    'url' => AssetMovementResource::getUrl(),
                ];
            });

            $sections[] = $this->section('Μετακινήσεις προς ενέργεια', 'Εγκρίσεις και εγκεκριμένες διακινήσεις που δεν έχουν ολοκληρωθεί.', $count, $critical, AssetMovementResource::getUrl(), $items);
        }

        if ($this->allowed('asset-maintenances.view')) {
            $query = AssetMaintenance::query()
                ->with('asset')
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->where(function (Builder $query) use ($maintenanceDeadline): void {
                    $query->whereDate('scheduled_at', '<=', $maintenanceDeadline)
                        ->orWhereDate('next_service_at', '<=', $maintenanceDeadline);
                });
            $count = (clone $query)->count();
            $critical = (clone $query)->where(function (Builder $query) use ($today): void {
                $query->whereDate('scheduled_at', '<', $today)
                    ->orWhereDate('next_service_at', '<', $today);
            })->count();
            $items = $query->orderByRaw('COALESCE(scheduled_at, next_service_at)')->limit(8)->get()->map(function (AssetMaintenance $maintenance) use ($today, $settings): array {
                $date = $maintenance->scheduled_at ?? $maintenance->next_service_at;
                $overdue = $date?->lt($today) ?? false;

                return [
                    'title' => ($maintenance->asset?->asset_code ?? 'Χωρίς κωδικό') . ' — ' . $maintenance->title,
                    'subtitle' => ($maintenance->asset?->name ?? 'Άγνωστο πάγιο') . ' · ' . ($date?->format('d/m/Y') ?? 'Χωρίς ημερομηνία'),
                    'badge' => $overdue ? 'Εκπρόθεσμη' : "Εντός {$settings->maintenance_warning_days} ημερών",
                    'color' => $overdue ? 'danger' : 'warning',
                    'url' => $this->recordUrl(AssetMaintenanceResource::class, 'asset-maintenances.update', $maintenance),
                ];
            });

            $sections[] = $this->section('Συντηρήσεις', "Εκπρόθεσμες και προγραμματισμένες εργασίες των επόμενων {$settings->maintenance_warning_days} ημερών.", $count, $critical, AssetMaintenanceResource::getUrl(), $items);
        }

        if ($this->allowed('assets.view')) {
            $query = Asset::query()
                ->whereNotNull('warranty_expires_at')
                ->whereDate('warranty_expires_at', '<=', $warrantyDeadline)
                ->whereNotIn('status', ['retired', 'sold']);
            $count = (clone $query)->count();
            $critical = (clone $query)->whereDate('warranty_expires_at', '<', $today)->count();
            $items = $query->orderBy('warranty_expires_at')->limit(8)->get()->map(function (Asset $asset) use ($today): array {
                $expired = $asset->warranty_expires_at?->lt($today) ?? false;

                return [
                    'title' => $asset->asset_code . ' — ' . $asset->name,
                    'subtitle' => 'Λήξη εγγύησης: ' . $asset->warranty_expires_at?->format('d/m/Y'),
                    'badge' => $expired ? 'Έληξε' : 'Λήγει σύντομα',
                    'color' => $expired ? 'danger' : 'warning',
                    'url' => $this->recordUrl(AssetResource::class, 'assets.update', $asset),
                ];
            });

            $sections[] = $this->section('Εγγυήσεις', "Εγγυήσεις ενεργών παγίων που έχουν λήξει ή λήγουν μέσα στις επόμενες {$settings->warranty_warning_days} ημέρες.", $count, $critical, AssetResource::getUrl(), $items);
        }

        if ($this->allowed('asset-insurances.view')) {
            $query = AssetInsurance::query()
                ->with('asset')
                ->whereDate('expires_at', '<=', $insuranceDeadline)
                ->whereHas('asset', fn (Builder $query) => $query->whereNotIn('status', ['retired', 'sold']));
            $count = (clone $query)->count();
            $critical = (clone $query)->whereDate('expires_at', '<', $today)->count();
            $items = $query->orderBy('expires_at')->limit(8)->get()->map(function (AssetInsurance $insurance) use ($today): array {
                $expired = $insurance->expires_at?->lt($today) ?? false;

                return [
                    'title' => ($insurance->asset?->asset_code ?? 'Χωρίς κωδικό') . ' — ' . $insurance->policy_number,
                    'subtitle' => $insurance->company . ' · Λήξη: ' . $insurance->expires_at?->format('d/m/Y'),
                    'badge' => $expired ? 'Έληξε' : 'Λήγει σύντομα',
                    'color' => $expired ? 'danger' : 'warning',
                    'url' => $this->recordUrl(AssetInsuranceResource::class, 'asset-insurances.update', $insurance),
                ];
            });

            $sections[] = $this->section('Ασφαλιστήρια', "Ασφαλιστήρια ενεργών παγίων που έχουν λήξει ή λήγουν μέσα στις επόμενες {$settings->insurance_warning_days} ημέρες.", $count, $critical, AssetInsuranceResource::getUrl(), $items);
        }

        if ($this->allowed('inventory.view')) {
            $query = InventoryCycle::query()->where('status', 'active');
            $count = (clone $query)->count();
            $critical = (clone $query)->whereNotNull('ends_at')->whereDate('ends_at', '<', $today)->count();
            $items = $query->orderBy('ends_at')->limit(8)->get()->map(function (InventoryCycle $cycle) use ($today): array {
                $overdue = $cycle->ends_at?->lt($today) ?? false;

                return [
                    'title' => $cycle->name,
                    'subtitle' => 'Λήξη: ' . ($cycle->ends_at?->format('d/m/Y') ?? 'Δεν έχει οριστεί'),
                    'badge' => $overdue ? 'Εκπρόθεσμη' : 'Ενεργή',
                    'color' => $overdue ? 'danger' : 'info',
                    'url' => route('inventory.deviations', $cycle),
                ];
            });

            $sections[] = $this->section('Φυσικές απογραφές', 'Ενεργές απογραφές και απογραφές που έχουν υπερβεί την ημερομηνία λήξης.', $count, $critical, InventoryCycleResource::getUrl(), $items);
        }

        if ($this->allowed('depreciation-periods.view')) {
            $query = DepreciationPeriod::query()->with('book')->whereIn('status', ['draft', 'posted']);
            $count = (clone $query)->count();
            $critical = (clone $query)->where('status', 'draft')->where(function (Builder $query): void {
                $query->where('year', '<', now()->year)
                    ->orWhere(fn (Builder $query) => $query->where('year', now()->year)->where('month', '<', now()->month));
            })->count();
            $items = $query->orderBy('year')->orderBy('month')->limit(8)->get()->map(function (DepreciationPeriod $period): array {
                return [
                    'title' => ($period->book?->name ?? 'Χωρίς βιβλίο') . ' — ' . sprintf('%02d/%d', $period->month, $period->year),
                    'subtitle' => $period->status === 'draft' ? 'Αναμένει προεπισκόπηση ή οριστικοποίηση' : 'Αναμένει κλείδωμα',
                    'badge' => $period->status === 'draft' ? 'Πρόχειρη' : 'Οριστικοποιημένη',
                    'color' => $period->status === 'draft' ? 'warning' : 'info',
                    'url' => DepreciationPeriodResource::getUrl(),
                ];
            });

            $sections[] = $this->section('Οικονομικές περίοδοι', 'Πρόχειρες περίοδοι αποσβέσεων και οριστικοποιημένες περίοδοι που δεν έχουν κλειδωθεί.', $count, $critical, DepreciationPeriodResource::getUrl(), $items);
        }

        if ($this->allowed('asset-receipts.view')) {
            $query = AssetReceipt::query()->with('supplier')->where('status', 'draft');
            $count = (clone $query)->count();
            $critical = (clone $query)->whereDate('receipt_date', '<', $today->copy()->subDays($settings->receipt_overdue_days))->count();
            $items = $query->orderBy('receipt_date')->limit(8)->get()->map(function (AssetReceipt $receipt) use ($today, $settings): array {
                $old = $receipt->receipt_date?->lt($today->copy()->subDays($settings->receipt_overdue_days)) ?? false;

                return [
                    'title' => $receipt->receipt_number . ' — ' . ($receipt->supplier?->name ?? 'Χωρίς προμηθευτή'),
                    'subtitle' => 'Παραλαβή: ' . ($receipt->receipt_date?->format('d/m/Y') ?? '—'),
                    'badge' => $old ? "Άνω των {$settings->receipt_overdue_days} ημερών" : 'Προς κεφαλαιοποίηση',
                    'color' => $old ? 'danger' : 'warning',
                    'url' => $this->recordUrl(AssetReceiptResource::class, 'asset-receipts.update', $receipt),
                ];
            });

            $sections[] = $this->section('Παραλαβές προς κεφαλαιοποίηση', 'Πρόχειρες παραλαβές που δεν έχουν ακόμη δημιουργήσει πάγια.', $count, $critical, AssetReceiptResource::getUrl(), $items);
        }

        if ($this->allowed('system-backups.view')) {
            $lastBackup = SystemBackup::query()->where('status', 'completed')->latest('completed_at')->first();
            $invalid = SystemBackup::query()->where('integrity_status', 'invalid')->count();
            $maximumAgeDays = $settings->backup_frequency === 'weekly' ? 8 : 2;
            $stale = $lastBackup === null || $lastBackup->completed_at?->lt(now()->subDays($maximumAgeDays));
            $count = $invalid + ($stale ? 1 : 0);
            $items = collect([
                [
                    'title' => 'Τελευταίο επιτυχές αντίγραφο',
                    'subtitle' => $lastBackup?->completed_at?->format('d/m/Y H:i') ?? 'Δεν έχει δημιουργηθεί αντίγραφο',
                    'badge' => $stale ? 'Απαιτείται ενέργεια' : 'Ενημερωμένο',
                    'color' => $stale ? 'danger' : 'success',
                    'url' => SystemBackupResource::getUrl(),
                ],
                [
                    'title' => 'Έλεγχος ακεραιότητας',
                    'subtitle' => $invalid ? "{$invalid} μη έγκυρα αντίγραφα" : 'Δεν εντοπίστηκαν αλλοιωμένα αντίγραφα',
                    'badge' => $invalid ? (string) $invalid : 'ΟΚ',
                    'color' => $invalid ? 'danger' : 'success',
                    'url' => SystemBackupResource::getUrl(),
                ],
            ]);

            $sections[] = $this->section('Αντίγραφα ασφαλείας', 'Κατάσταση επιχειρησιακής συνέχειας και ακεραιότητας αντιγράφων.', $count, $count, SystemBackupResource::getUrl(), $items);
        }

        if ($this->allowed('assets.view')) {
            $missing = Asset::query()->where(function (Builder $query): void {
                $query->whereNull('asset_category_id')->orWhereNull('location_id')->orWhereNull('asset_custodian_id');
            })->count();
            $duplicates = Asset::query()->select('serial_number', DB::raw('COUNT(*) AS total'))->whereNotNull('serial_number')->where('serial_number', '!=', '')->groupBy('serial_number')->having('total', '>', 1)->get()->count();
            $values = Asset::query()->where(function (Builder $query): void {
                $query->where('remaining_value', '<', 0)
                    ->orWhereColumn('remaining_value', '>', 'purchase_value')
                    ->orWhereColumn('remaining_value', '<', 'residual_value');
            })->count();
            $count = $missing + $duplicates + $values;
            $items = collect([
                ['title' => 'Ελλιπή βασικά στοιχεία', 'subtitle' => 'Χωρίς κατηγορία, τοποθεσία ή υπεύθυνο', 'badge' => (string) $missing, 'color' => $missing ? 'danger' : 'success', 'url' => DataQualityChecks::getUrl()],
                ['title' => 'Διπλοί σειριακοί αριθμοί', 'subtitle' => 'Ομάδες εγγραφών με κοινό serial number', 'badge' => (string) $duplicates, 'color' => $duplicates ? 'danger' : 'success', 'url' => DataQualityChecks::getUrl()],
                ['title' => 'Παράδοξες οικονομικές αξίες', 'subtitle' => 'Αρνητική ή ασύμβατη αναπόσβεστη αξία', 'badge' => (string) $values, 'color' => $values ? 'danger' : 'success', 'url' => DataQualityChecks::getUrl()],
            ]);

            $sections[] = $this->section('Ποιότητα δεδομένων', 'Συγκεντρωτική ένδειξη κρίσιμων αποκλίσεων του μητρώου.', $count, $count, DataQualityChecks::getUrl(), $items);
        }

        return [
            'sections' => collect($sections),
            'totalItems' => collect($sections)->sum('count'),
            'criticalItems' => collect($sections)->sum('critical'),
            'updatedAt' => now(),
        ];
    }

    private function allowed(string $permission): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can($permission);
    }

    private function section(string $title, string $description, int $count, int $critical, string $url, $items): array
    {
        return compact('title', 'description', 'count', 'critical', 'url', 'items');
    }

    private function recordUrl(string $resource, string $updatePermission, $record): string
    {
        return $this->allowed($updatePermission)
            ? $resource::getUrl('edit', ['record' => $record])
            : $resource::getUrl();
    }
}
