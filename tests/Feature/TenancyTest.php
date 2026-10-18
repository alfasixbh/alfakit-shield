<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Tests\Fixtures\Models\Team;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldTenantUser;
use Spatie\Permission\Models\Role;

uses(AlfaSix\AlfakitShield\Tests\TenancyTestCase::class);

/**
 * Two teams the user belongs to, with an `editor` role in the first one only.
 *
 * @return array{0: ShieldTenantUser, 1: Team, 2: Team}
 */
function shieldTeams(object $test, array $permissions = ['view_reports_page']): array
{
    $first = Team::query()->create(['name' => 'First', 'slug' => 'first']);
    $second = Team::query()->create(['name' => 'Second', 'slug' => 'second']);

    setPermissionsTeamId($first->id);
    /** @var ShieldTenantUser $user */
    $user = $test->makeUser(['editor' => $permissions]);
    setPermissionsTeamId(null);

    $user->teams()->attach([$first->id, $second->id]);

    return [$user, $first, $second];
}

it('scopes roles to tenants when the panel has tenancy and teams are on', function (): void {
    $panel = alfakit()->getPanel('shield-app');

    expect(ShieldPlugin::get($panel)->isScopedToTenant($panel))->toBeTrue()
        ->and(ShieldPlugin::get(alfakit()->getPanel('shield'))->isScopedToTenant(alfakit()->getPanel('shield')))->toBeFalse()
        ->and(ShieldPlugin::make()->scopeToTenant(false)->isScopedToTenant($panel))->toBeFalse();
});

it('checks permissions within the current tenant', function (): void {
    [$user, $first, $second] = shieldTeams($this);
    $this->actingAs($user);

    $this->inertiaGet('/shield-app/first/reports')->assertOk();
    $this->inertiaGet('/shield-app/second/reports')->assertForbidden();
});

it('lists and creates the roles of the current tenant', function (): void {
    $this->generateAndRegisterPolicies('shield-app');
    [$user, $first, $second] = shieldTeams($this);
    setPermissionsTeamId($first->id);
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    setPermissionsTeamId(null);

    Role::query()->create(['name' => 'other-team-role', 'guard_name' => 'web', 'team_id' => $second->id]);

    $this->actingAs($user);

    $page = $this->inertiaProps('/shield-app/first/shield/roles')['page'];
    $table = collect($page['content'])->firstWhere('type', 'Table') ?? $page['content'][0];
    $names = Role::query()->whereIn('id', collect($table['records'])->pluck('key'))->pluck('name')->all();

    expect($names)->toContain('editor')
        ->and(in_array('other-team-role', $names, true))->toBeFalse();

    $token = $this->inertiaProps('/shield-app/first/shield/roles/create')['page']['token'];

    $this->runAction('/shield-app/first/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => [
        'name' => 'reviewer',
        'guard_name' => 'web',
        'permissions' => ['pages' => ['view_reports_page']],
    ]])->assertRedirect();

    $role = Role::query()->where('name', 'reviewer')->firstOrFail();

    expect($role->getAttribute('team_id'))->toBe($first->id)
        ->and($role->permissions->pluck('name')->all())->toBe(['view_reports_page']);

    // Not a super admin in the second team.
    $this->inertiaGet('/shield-app/second/shield/roles')->assertForbidden();
});

it('allows the same role name in different tenants', function (): void {
    [$user, $first, $second] = shieldTeams($this);
    setPermissionsTeamId($second->id);
    $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    setPermissionsTeamId(null);

    $this->actingAs($user);
    $token = $this->inertiaProps('/shield-app/second/shield/roles/create')['page']['token'];

    $this->runAction('/shield-app/second/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => ['name' => 'editor', 'guard_name' => 'web']])
        ->assertRedirect();

    expect(Role::query()->where('name', 'editor')->pluck('team_id')->sort()->values()->all())->toBe([$first->id, $second->id]);
});

it('assigns the super admin role within a tenant with the command', function (): void {
    $this->generateAndRegisterPolicies('shield-app');
    [$user, $first, $second] = shieldTeams($this);

    $this->artisan('alfakit-shield:super-admin', ['--user' => $user->id, '--panel' => 'shield-app'])->assertFailed();
    $this->artisan('alfakit-shield:super-admin', ['--user' => $user->id, '--panel' => 'shield-app', '--tenant' => $second->id])->assertSuccessful();

    $this->actingAs($user->fresh());

    // Super admin in the second team only.
    $this->inertiaGet('/shield-app/second/shield/roles')->assertOk();
    $this->inertiaGet('/shield-app/first/shield/roles')->assertForbidden();
});
