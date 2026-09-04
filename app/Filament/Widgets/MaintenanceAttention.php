<?php

namespace App\Filament\Widgets;

use App\Models\AssetMaintenance;
use App\Models\ApplicationSetting;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MaintenanceAttention extends StatsOverviewWidget
{
    public static function canView(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-maintenances.view');
    }

    protected function getStats(): array
    {
        $today = now()->toDateString();
        $days = ApplicationSetting::current()->maintenance_warning_days;
        $deadline = now()->addDays($days)->toDateString();

        $overdue = AssetMaintenance::query()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('scheduled_at', '<', $today)
            ->count();

        $upcoming = AssetMaintenance::query()
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereBetween('scheduled_at', [$today, $deadline])
            ->count();

        return [
            Stat::make('Εκκρεμείς συντηρήσεις', $overdue)->color($overdue ? 'danger' : 'success'),
            Stat::make("Συντηρήσεις επόμενων {$days} ημερών", $upcoming)->color($upcoming ? 'warning' : 'success'),
        ];
    }
}
