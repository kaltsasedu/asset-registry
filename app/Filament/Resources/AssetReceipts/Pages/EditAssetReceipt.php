<?php

namespace App\Filament\Resources\AssetReceipts\Pages;

use App\Filament\Resources\AssetReceipts\AssetReceiptResource;
use App\Services\AssetReceiptCapitalizationService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAssetReceipt extends EditRecord
{
    protected static string $resource = AssetReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('capitalize')->label('Κεφαλαιοποίηση σε πάγια')->requiresConfirmation()
                ->visible(fn (): bool => $this->record->status === 'draft' && AssetReceiptResource::canPerform('capitalize'))
                ->action(function (): void {
                    $count = app(AssetReceiptCapitalizationService::class)->capitalize($this->record);
                    Notification::make()->success()->title('Η κεφαλαιοποίηση ολοκληρώθηκε')->body("Δημιουργήθηκαν {$count} πάγια.")->send();
                    $this->redirect(AssetReceiptResource::getUrl('index'));
                }),
            DeleteAction::make()->visible(fn (): bool => $this->record->status === 'draft'),
        ];
    }

    protected function beforeSave(): void
    {
        if ($this->record->status === 'capitalized') {
            throw ValidationException::withMessages([
                'receipt' => 'Η κεφαλαιοποιημένη παραλαβή δεν μεταβάλλεται. Οι μεταβολές γίνονται πλέον από τα αντίστοιχα πάγια.',
            ]);
        }
    }
}
