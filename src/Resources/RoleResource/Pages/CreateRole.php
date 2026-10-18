<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Resources\RoleResource\Pages;

use AlfaSix\Alfakit\Resources\Pages\CreateRecord;
use AlfaSix\AlfakitShield\Resources\RoleResource;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /**
     * Creates the role (in the current tenant's team when roles are tenant-scoped) and
     * gives it the checked permissions.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        /** @var class-string<RoleResource> $resource */
        $resource = static::getResource();
        $permissions = $resource::pullPermissions($data);

        $model = $resource::getModel();
        /** @var Role $role */
        $role = new $model;
        $role->fill($data);

        $tenant = $this->getPanelTenant();

        if ($tenant !== null && $resource::isScopedToTenant()) {
            $role->setAttribute(PermissionModels::teamForeignKey(), $tenant->getKey());
        }

        $role->save();

        $resource::syncRolePermissions($role, $permissions);

        return $role;
    }
}
