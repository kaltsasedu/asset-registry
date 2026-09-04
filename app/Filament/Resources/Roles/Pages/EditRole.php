<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $selectedPermissions = [];

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $permissions = $this->record->permissions->pluck('name')->all();

        $data['monitoring_permissions'] = array_values(array_intersect(
            $permissions,
            array_keys(RoleResource::monitoringPermissions()),
        ));
        $data['registry_permissions'] = array_values(array_intersect(
            $permissions,
            array_keys(RoleResource::registryPermissions()),
        ));
        $data['settings_permissions'] = array_values(array_intersect(
            $permissions,
            array_keys(RoleResource::settingsPermissions()),
        ));

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->selectedPermissions = array_values(array_unique(array_merge(
            $data['monitoring_permissions'] ?? [],
            $data['registry_permissions'] ?? [],
            $data['settings_permissions'] ?? [],
        )));

        unset(
            $data['monitoring_permissions'],
            $data['registry_permissions'],
            $data['settings_permissions'],
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncPermissions($this->selectedPermissions);
    }

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
