<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitShield\Support\PermissionRegistry;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Helpers shared by the traits, the role resource and the commands.
 */
final class Shield
{
    /**
     * The permission registry of a panel that uses the plugin, or one with the default
     * options when it doesn't (so traits still compute the same permission names).
     */
    public static function registry(?Panel $panel = null): PermissionRegistry
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();

        return (ShieldPlugin::get($panel) ?? new ShieldPlugin)->getPermissionRegistry($panel);
    }

    /**
     * Whether the panel's signed-in user has the permission (super admins always do).
     */
    public static function userCan(string $permission, ?Panel $panel = null): bool
    {
        $panel ??= alfakit()->getCurrentOrDefaultPanel();
        $user = $panel->auth()->user();

        return $user instanceof Authorizable && $user->can($permission);
    }

    /**
     * Whether the user has the panel's super admin role.
     */
    public static function isSuperAdmin(?Authenticatable $user, ?Panel $panel = null): bool
    {
        $plugin = ShieldPlugin::get($panel);

        if ($plugin === null || ! $plugin->isSuperAdminEnabled() || $user === null || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return (bool) $user->hasRole($plugin->getSuperAdminRole());
    }
}
