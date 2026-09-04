<?php

namespace App\Filament\Pages;

use App\Support\HelpContent;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class HelpCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;
    protected string $view = 'filament.pages.help-center';

    public static function getNavigationLabel(): string { return 'Εγχειρίδιο Χρήσης'; }
    public function getTitle(): string { return 'Κέντρο Βοήθειας και Εγχειρίδιο Χρήσης'; }
    public static function getNavigationGroup(): ?string { return 'Βοήθεια'; }
    public static function getNavigationSort(): ?int { return 1; }
    public static function canAccess(): bool { return auth()->check(); }

    protected function getViewData(): array
    {
        return ['sections' => HelpContent::manualSections()];
    }
}
