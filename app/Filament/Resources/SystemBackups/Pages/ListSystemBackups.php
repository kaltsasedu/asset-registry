<?php

namespace App\Filament\Resources\SystemBackups\Pages;

use App\Filament\Resources\SystemBackups\SystemBackupResource;
use Filament\Resources\Pages\ListRecords;

class ListSystemBackups extends ListRecords
{
    protected static string $resource = SystemBackupResource::class;

    public function getSubheading(): ?string
    {
        return 'Τα αντίγραφα αποθηκεύονται ιδιωτικά. Για την αυτόματη εκτέλεση πρέπει να λειτουργεί ο προγραμματιστής εργασιών Laravel.';
    }
}
