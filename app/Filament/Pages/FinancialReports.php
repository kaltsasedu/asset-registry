<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class FinancialReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentChartBar;
    protected string $view = 'filament.pages.financial-reports';

    public static function getNavigationLabel(): string { return 'Οικονομικές Αναφορές'; }
    public function getTitle(): string { return 'Οικονομικές Αναφορές'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 6; }

    public static function getNavigationUrl(): string
    {
        return route('depreciation.reports');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('depreciation-periods.view');
    }
}
