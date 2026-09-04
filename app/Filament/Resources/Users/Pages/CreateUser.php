<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! UserResource::canManageDataScopes()) {
            $data['data_scope'] = 'restricted';
            $data['include_responsible_assets'] = false;
        }

        return $data;
    }
}
