<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class AssetImport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;
    protected string $view = 'filament.pages.asset-import';

    public static function getNavigationLabel(): string { return 'Εισαγωγή Παγίων'; }
    public function getTitle(): string { return 'Εισαγωγή Παγίων'; }
    public static function getNavigationGroup(): ?string { return 'Παρακολούθηση'; }
    public static function getNavigationSort(): ?int { return 2; }
    public static function getNavigationUrl(): string { return route('assets.import'); }
    public static function canAccess(): bool { return auth()->user()?->hasRole('Διαχειριστής') || auth()->user()?->can('assets.import'); }
}
