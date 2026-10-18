<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Traits;

use AlfaSix\Alfakit\Panel;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use Illuminate\Database\Eloquent\Model;

/**
 * Use on the user model (with spatie's `HasRoles`): new users get the panel user role and
 * `canAccessPanel()` requires the super admin or panel user role on panels using Shield.
 *
 * @mixin Model
 */
trait HasPanelShield
{
    public static function bootHasPanelShield(): void
    {
        static::created(static function (Model $user): void {
            if (! method_exists($user, 'assignRole')) {
                return;
            }

            // The current panel when it uses Shield, else every panel that does.
            $current = alfakit()->getCurrentPanel();
            $panels = $current !== null && ShieldPlugin::get($current) !== null ? [$current] : alfakit()->getPanels();
            $assigned = [];

            foreach ($panels as $panel) {
                $plugin = ShieldPlugin::get($panel);

                if ($plugin === null || ! $plugin->isPanelUserEnabled()) {
                    continue;
                }

                // Tenant-scoped roles can only be assigned within a tenant.
                if ($plugin->isScopedToTenant($panel) && getPermissionsTeamId() === null) {
                    continue;
                }

                $role = PermissionModels::findOrCreateRole($plugin->getPanelUserRole(), $plugin->getGuard($panel));

                if (! isset($assigned[$role->getKey()])) {
                    $user->assignRole($role);
                    $assigned[$role->getKey()] = true;
                }
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $plugin = ShieldPlugin::get($panel);

        if ($plugin === null) {
            return true;
        }

        // Without a panel user role, every signed-in user may enter (as before Shield).
        if (! $plugin->isPanelUserEnabled()) {
            return true;
        }

        return $this->hasAnyRole(array_filter([
            $plugin->getPanelUserRole(),
            $plugin->isSuperAdminEnabled() ? $plugin->getSuperAdminRole() : null,
        ]));
    }
}
