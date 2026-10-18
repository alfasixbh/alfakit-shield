<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Support;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * The role and permission models configured for spatie/laravel-permission.
 */
final class PermissionModels
{
    /**
     * @return class-string<Role>
     */
    public static function role(): string
    {
        /** @var class-string<Role> */
        return config('permission.models.role', Role::class);
    }

    /**
     * @return class-string<Permission>
     */
    public static function permission(): string
    {
        /** @var class-string<Permission> */
        return config('permission.models.permission', Permission::class);
    }

    public static function teamForeignKey(): string
    {
        return (string) config('permission.column_names.team_foreign_key', 'team_id');
    }

    /**
     * Create the permissions that don't exist yet for the guard.
     *
     * @param  iterable<string>  $names
     */
    public static function ensurePermissions(iterable $names, string $guard): void
    {
        $model = self::permission();
        $created = false;

        foreach ($names as $name) {
            if (! $model::query()->where('name', $name)->where('guard_name', $guard)->exists()) {
                $model::query()->create(['name' => $name, 'guard_name' => $guard]);
                $created = true;
            }
        }

        if ($created) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    /**
     * Find or create a role of the guard, in the given team when teams are enabled.
     */
    public static function findOrCreateRole(string $name, string $guard, int|string|Model|null $team = null): Role
    {
        $model = self::role();
        $query = $model::query()->where('name', $name)->where('guard_name', $guard);
        $attributes = ['name' => $name, 'guard_name' => $guard];

        if (config('permission.teams')) {
            $teamKey = $team instanceof Model ? $team->getKey() : $team;
            $query->where(self::teamForeignKey(), $teamKey);
            $attributes[self::teamForeignKey()] = $teamKey;
        }

        $role = $query->first();

        if ($role instanceof Role) {
            return $role;
        }

        $role = new $model;
        $role->forceFill($attributes)->save();

        return $role;
    }
}
