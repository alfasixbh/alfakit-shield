<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Resources\RoleResource\Pages;

use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Resources\Pages\EditRecord;
use AlfaSix\AlfakitShield\Resources\RoleResource;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /**
     * Saves the role and replaces its permissions with the checked ones.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var class-string<RoleResource> $resource */
        $resource = static::getResource();
        $permissions = $resource::pullPermissions($data);

        $record->fill($data)->save();

        if ($record instanceof Role) {
            $resource::syncRolePermissions($record, $permissions);
        }

        return $record;
    }
}
