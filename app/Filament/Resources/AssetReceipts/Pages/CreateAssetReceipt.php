<?php

namespace App\Filament\Resources\AssetReceipts\Pages;

use App\Filament\Resources\AssetReceipts\AssetReceiptResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAssetReceipt extends CreateRecord
{
    protected static string $resource = AssetReceiptResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Η παραλαβή δημιουργήθηκε. Ανοίξτε την για να προσθέσετε γραμμές.';
    }
}
