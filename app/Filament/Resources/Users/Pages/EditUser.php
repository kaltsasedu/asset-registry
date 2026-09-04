<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! UserResource::canManageDataScopes()) {
            $data['data_scope'] = $this->record->data_scope;
            $data['include_responsible_assets'] = $this->record->include_responsible_assets;
        }

        return $data;
    }
}
