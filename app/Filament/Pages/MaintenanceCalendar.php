<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AssetMaintenances\AssetMaintenanceResource;
use App\Models\AssetMaintenance;
use BackedEnum;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class MaintenanceCalendar extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected string $view = 'filament.pages.maintenance-calendar';

    public static function getNavigationLabel(): string { return 'Ημερολόγιο Συντηρήσεων'; }
    public function getTitle(): string { return 'Ημερολόγιο Συντηρήσεων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 9; }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('asset-maintenances.view');
    }

    protected function getViewData(): array
    {
        $month = Carbon::createFromFormat('Y-m', request()->query('month', now()->format('Y-m')))->startOfMonth();
        $start = $month->copy()->startOfWeek(Carbon::MONDAY);
        $end = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $scheduled = AssetMaintenance::query()
            ->with(['asset', 'assignee'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereBetween('scheduled_at', [$start->toDateString(), $end->toDateString()])
            ->get();

        $legacyNextServices = AssetMaintenance::query()
            ->with(['asset', 'assignee'])
            ->whereNotNull('next_service_at')
            ->doesntHave('generatedMaintenance')
            ->whereBetween('next_service_at', [$start->toDateString(), $end->toDateString()])
            ->get();

        $events = $scheduled->merge($legacyNextServices)->unique('id')->groupBy(function (AssetMaintenance $maintenance): string {
            return ($maintenance->scheduled_at ?? $maintenance->next_service_at)->format('Y-m-d');
        });

        $weeks = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $week = [];
            for ($day = 0; $day < 7; $day++) {
                $date = $cursor->copy();
                $week[] = [
                    'date' => $date,
                    'events' => $events->get($date->format('Y-m-d'), collect()),
                ];
                $cursor->addDay();
            }
            $weeks[] = $week;
        }

        return [
            'month' => $month,
            'weeks' => $weeks,
            'previousMonth' => $month->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
            'resource' => AssetMaintenanceResource::class,
        ];
    }
}
