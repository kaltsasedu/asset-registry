<?php

namespace App\Filament\Widgets;

use App\Models\ApplicationSetting;
use App\Models\Asset;
use Filament\Widgets\ChartWidget;

class AssetReplacementChart extends ChartWidget
{
    protected ?string $heading = 'Προγραμματισμός αντικατάστασης';

    protected function getData(): array
    {
        $days = ApplicationSetting::current()->warranty_warning_days;
        $fullyDepreciated = Asset::whereColumn('remaining_value', '<=', 'residual_value')->count();
        $underRepair = Asset::where('status', 'under_repair')->count();
        $warrantyExpiring = Asset::whereBetween('warranty_expires_at', [
            now()->toDateString(),
            now()->addDays($days)->toDateString(),
        ])->count();

        return [
            'datasets' => [[
                'data' => [$fullyDepreciated, $underRepair, $warrantyExpiring],
                'backgroundColor' => ['#ef4444', '#f59e0b', '#3b82f6'],
            ]],
            'labels' => [
                'Πλήρως αποσβεσμένα',
                'Σε επισκευή',
                "Εγγύηση λήγει σε {$days} ημέρες",
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
