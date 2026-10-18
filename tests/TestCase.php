<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests;

use AlfaSix\Alfakit\Tests\Fixtures\Models\User;
use AlfaSix\Alfakit\Tests\TestCase as AlfakitTestCase;
use AlfaSix\AlfakitShield\AlfakitShieldServiceProvider;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldPanelProvider;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldTenantPanelProvider;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldTenantUser;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldUser;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends AlfakitTestCase
{
    /** Whether spatie teams are on (roles scoped to tenants). */
    protected bool $usesTeams = false;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        setPermissionsTeamId(null);

        // Generated policies get their own namespace and folder per test, so they can be
        // loaded without clashing with the classes of other tests.
        $suffix = 'P'.str_replace('.', '', uniqid('', true));
        config()->set('alfakit-shield.policies.namespace', 'AlfaSix\\AlfakitShield\\Tests\\Generated\\'.$suffix);
        config()->set('alfakit-shield.policies.path', sys_get_temp_dir().'/alfakit-shield-policies/'.$suffix);
    }

    protected function tearDown(): void
    {
        $path = (string) config('alfakit-shield.policies.path');

        if (is_dir($path)) {
            (new Filesystem)->deleteDirectory($path);
        }

        parent::tearDown();
    }

    /**
     * A user with the given roles (created with their permissions when missing).
     *
     * @param  array<string, list<string>>  $roles  role name => permissions
     */
    public function makeUser(array $roles = [], string $email = 'user@example.com'): User
    {
        /** @var ShieldUser|ShieldTenantUser $user */
        $user = config('auth.providers.users.model')::query()->create(['name' => 'User', 'email' => $email, 'password' => 'password']);

        foreach ($roles as $name => $permissions) {
            $role = PermissionModels::findOrCreateRole($name, 'web', getPermissionsTeamId());
            PermissionModels::ensurePermissions($permissions, 'web');
            $role->givePermissionTo($permissions);
            $user->assignRole($role);
        }

        return $user;
    }

    /**
     * Run `alfakit-shield:generate` for the panel and register the generated policies.
     */
    public function generateAndRegisterPolicies(string $panel = 'shield'): void
    {
        $this->artisan('alfakit-shield:generate', ['--panel' => $panel])->assertSuccessful();

        $namespace = (string) config('alfakit-shield.policies.namespace');

        foreach (glob(config('alfakit-shield.policies.path').'/*Policy.php') ?: [] as $file) {
            require_once $file;
        }

        foreach (ShieldPlugin::get(alfakit()->getPanel($panel))?->getPermissionRegistry(alfakit()->getPanel($panel))->resources() ?? [] as $resource) {
            Gate::policy($resource['model'], $namespace.'\\'.class_basename($resource['model']).'Policy');
        }
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PermissionServiceProvider::class,
            AlfakitShieldServiceProvider::class,
            ShieldPanelProvider::class,
            ShieldTenantPanelProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', ShieldUser::class);
        $app['config']->set('permission.teams', $this->usesTeams);
        $app['config']->set('permission.cache.store', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        parent::defineDatabaseMigrations();

        // spatie ships its migration as a stub: copy it into a migrations folder.
        $directory = sys_get_temp_dir().'/alfakit-shield-migrations';
        $target = $directory.'/2020_01_01_000000_create_permission_tables.php';

        if (! is_file($target)) {
            @mkdir($directory, 0777, true);
            copy(dirname(__DIR__, 4).'/vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub', $target);
        }

        $this->loadMigrationsFrom($directory);
    }

    // ---------------------------------------------------------------- Inertia helpers

    /**
     * Perform an Inertia GET.
     */
    public function inertiaGet(string $url): TestResponse
    {
        return $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => '']);
    }

    /**
     * The Inertia props of a page (asserting it rendered).
     *
     * @return array<string, mixed>
     */
    public function inertiaProps(string $url): array
    {
        $response = $this->inertiaGet($url);
        $response->assertOk();

        return (array) $response->json('props');
    }

    /**
     * Run an action through the internal endpoint, as the frontend does.
     *
     * @param  array<string, mixed>  $data
     */
    public function runAction(string $url, array $data): TestResponse
    {
        return $this->post($url, $data, ['X-Inertia' => 'true', 'X-Inertia-Version' => '', 'Accept' => 'application/json']);
    }
}
