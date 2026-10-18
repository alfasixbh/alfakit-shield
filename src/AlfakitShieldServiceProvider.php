<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield;

use AlfaSix\Alfakit\Events\TenantSet;
use AlfaSix\AlfakitShield\Commands\GenerateCommand;
use AlfaSix\AlfakitShield\Commands\InstallCommand;
use AlfaSix\AlfakitShield\Commands\SuperAdminCommand;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AlfakitShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/alfakit-shield.php', 'alfakit-shield');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'alfakit-shield');

        if ($this->app->runningInConsole()) {
            $this->commands([GenerateCommand::class, SuperAdminCommand::class, InstallCommand::class]);

            $this->publishes([__DIR__.'/../config/alfakit-shield.php' => config_path('alfakit-shield.php')], 'alfakit-shield-config');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/alfakit-shield')], 'alfakit-shield-translations');
            $this->publishes([__DIR__.'/../stubs' => base_path('stubs/alfakit-shield')], 'alfakit-shield-stubs');
        }

        $this->registerSuperAdminGate();
        $this->registerTenantTeams();
        $this->registerRolePolicy();
    }

    /**
     * Super admins pass every check on panels whose plugin uses `superAdmin(gate: true)`.
     */
    protected function registerSuperAdminGate(): void
    {
        Gate::before(static function (mixed $user): ?bool {
            if (! $user instanceof Authenticatable) {
                return null;
            }

            $plugin = ShieldPlugin::get();

            if ($plugin === null || ! $plugin->definesSuperAdminViaGate()) {
                return null;
            }

            return Shield::isSuperAdmin($user) ? true : null;
        });
    }

    /**
     * On tenant-scoped panels, permission checks use the current tenant as spatie's team.
     */
    protected function registerTenantTeams(): void
    {
        Event::listen(TenantSet::class, static function (TenantSet $event): void {
            $panel = alfakit()->getCurrentPanel();

            if ($panel === null || ! (ShieldPlugin::get($panel)?->isScopedToTenant($panel) ?? false)) {
                return;
            }

            setPermissionsTeamId($event->tenant->getKey());

            if ($event->user !== null && method_exists($event->user, 'unsetRelation')) {
                $event->user->unsetRelation('roles')->unsetRelation('permissions');
            }
        });
    }

    protected function registerRolePolicy(): void
    {
        $this->app->booted(static function (): void {
            if (! config('alfakit-shield.register_role_policy', true)) {
                return;
            }

            $policy = rtrim((string) config('alfakit-shield.policies.namespace', 'App\\Policies'), '\\').'\\RolePolicy';

            if (class_exists($policy)) {
                Gate::policy(PermissionModels::role(), $policy);
            }
        });
    }
}
