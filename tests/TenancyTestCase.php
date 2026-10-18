<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Tests;

use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldTenantUser;

/**
 * Spatie teams on: roles and role assignments belong to a tenant (team).
 */
abstract class TenancyTestCase extends TestCase
{
    protected bool $usesTeams = true;

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('auth.providers.users.model', ShieldTenantUser::class);
    }
}
