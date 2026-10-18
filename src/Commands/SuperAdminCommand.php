<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Commands;

use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\text;

#[AsCommand(name: 'alfakit-shield:super-admin', description: 'Give a user the super admin role of a panel using Shield')]
class SuperAdminCommand extends Command
{
    protected $signature = 'alfakit-shield:super-admin
        {--user= : The id of the user}
        {--panel= : The panel (default: the default panel)}
        {--tenant= : The tenant key, on panels whose roles are scoped to tenants}';

    public function handle(): int
    {
        $panelId = $this->option('panel');

        if (is_string($panelId) && $panelId !== '' && ! alfakit()->hasPanel($panelId)) {
            $this->components->error("Panel [{$panelId}] is not registered.");

            return self::FAILURE;
        }

        $panel = alfakit()->getPanel(is_string($panelId) && $panelId !== '' ? $panelId : null);
        $plugin = ShieldPlugin::get($panel);

        if ($plugin === null) {
            $this->components->error("Panel [{$panel->getId()}] doesn't use ShieldPlugin.");

            return self::FAILURE;
        }

        if (! $plugin->isSuperAdminEnabled()) {
            $this->components->error("The super admin role is disabled on panel [{$panel->getId()}].");

            return self::FAILURE;
        }

        $guard = $plugin->getGuard($panel);
        $provider = config("auth.guards.{$guard}.provider");
        $model = config("auth.providers.{$provider}.model");

        if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
            $this->components->error("Could not resolve the user model of guard [{$guard}].");

            return self::FAILURE;
        }

        $user = $this->resolveUser($model);

        if ($user === null || ! method_exists($user, 'assignRole')) {
            $this->components->error($user === null ? 'User not found.' : 'The user model must use the Spatie\\Permission\\Traits\\HasRoles trait.');

            return self::FAILURE;
        }

        if ($plugin->isScopedToTenant($panel)) {
            $tenant = $this->option('tenant');

            if (! is_scalar($tenant) || $tenant === '') {
                $this->components->error("Roles of panel [{$panel->getId()}] are scoped to tenants: pass --tenant=<key>.");

                return self::FAILURE;
            }

            setPermissionsTeamId(is_int($tenant) ? $tenant : (string) $tenant);
        }

        // A global role (no team), assigned within the current team when teams are on.
        $role = PermissionModels::findOrCreateRole($plugin->getSuperAdminRole(), $guard);

        if (! $plugin->definesSuperAdminViaGate()) {
            $permissions = $plugin->getPermissionRegistry($panel)->allPermissions();
            PermissionModels::ensurePermissions($permissions, $guard);
            $role->givePermissionTo($permissions);
        }

        $user->assignRole($role);

        $this->components->info("User [{$user->getKey()}] is now a super admin of panel [{$panel->getId()}].");

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $model
     */
    protected function resolveUser(string $model): ?Model
    {
        $id = $this->option('user');

        if (! is_scalar($id) || $id === '') {
            $query = $model::query();

            if ($query->count() === 1) {
                return $query->first();
            }

            $id = text('The id of the user to make super admin', required: true);
        }

        return $model::query()->find($id);
    }
}
