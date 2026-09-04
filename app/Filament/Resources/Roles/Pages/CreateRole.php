<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->selectedPermissions = $this->selectedPermissionsFrom($data);

        unset(
            $data['monitoring_permissions'],
            $data['registry_permissions'],
            $data['settings_permissions'],
        );

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    private function selectedPermissionsFrom(array $data): array
    {
        return array_values(array_unique(array_merge(
            $data['monitoring_permissions'] ?? [],
            $data['registry_permissions'] ?? [],
            $data['settings_permissions'] ?? [],
        )));
    }
}
