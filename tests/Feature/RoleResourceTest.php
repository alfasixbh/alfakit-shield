<?php

declare(strict_types=1);

use AlfaSix\AlfakitShield\Resources\RoleResource;
use AlfaSix\AlfakitShield\Shield;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(AlfaSix\AlfakitShield\Tests\TestCase::class);

/**
 * The components of the role form, flattened, by type and name.
 *
 * @return list<array<string, mixed>>
 */
function shieldFlatten(array $node): array
{
    $found = [];

    if (isset($node['type'])) {
        $found[] = $node;
    }

    foreach ($node as $key => $value) {
        if (is_array($value) && $key !== 'options') {
            array_push($found, ...shieldFlatten($value));
        }
    }

    return $found;
}

it('renders the role form with a tab per entity type', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));

    $page = $this->inertiaProps('/shield/shield/roles/create')['page'];
    $components = shieldFlatten($page['content']);

    $tabs = array_values(array_filter($components, fn (array $c): bool => $c['type'] === 'Tab'));
    $lists = array_values(array_filter($components, fn (array $c): bool => $c['type'] === 'CheckboxList'));
    $sections = array_values(array_filter($components, fn (array $c): bool => $c['type'] === 'Section' && isset($c['heading'])));

    expect(array_column($tabs, 'label'))->toBe(['Resources', 'Pages', 'Widgets', 'Custom permissions'])
        ->and(array_column($lists, 'statePath'))->toBe([
            'permissions.resource_post', 'permissions.resource_category', 'permissions.resource_role',
            'permissions.pages', 'permissions.widgets', 'permissions.custom',
        ])
        ->and(array_column($sections, 'heading'))->toBe(['Posts', 'Categories', 'Roles'])
        ->and(array_column($lists[0]['options'], 'value'))->toContain('view_any_post', 'force_delete_post')
        ->and(collect($lists[4]['options'])->firstWhere('value', 'view_sales_widget')['label'])->toBe('Sales Widget')
        ->and(collect($components)->firstWhere('statePath', 'select_all')['type'])->toBe('Toggle');
});

it('gives each resource card and permission tab its whole width', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));

    $components = shieldFlatten($this->inertiaProps('/shield/shield/roles/create')['page']['content']);
    $sections = array_values(array_filter($components, fn (array $c): bool => $c['type'] === 'Section' && isset($c['heading']) && $c['heading'] !== null));
    $grid = collect($components)->firstWhere('type', 'Grid');

    // A two-column section (or a half-width grid in the tab) squeezed the checkbox labels
    // into each other ("ViewView Any").
    expect($sections)->not->toBeEmpty()
        ->and(collect($sections)->pluck('columns')->unique()->values()->all())->toBe([['default' => 1, 'lg' => 1]])
        ->and($grid['columnSpan']['default'] ?? null)->toBe('full');
});

it('creates a role with the selected permissions', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    $token = $this->inertiaProps('/shield/shield/roles/create')['page']['token'];

    // Permissions the form doesn't offer are rejected.
    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => [
        'name' => 'editor',
        'guard_name' => 'web',
        'permissions' => ['widgets' => ['delete_everything']],
    ]])->assertJsonValidationErrors(['permissions.widgets.0']);

    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => [
        'name' => 'editor',
        'guard_name' => 'web',
        'permissions' => [
            'resource_post' => ['view_any_post', 'update_post'],
            'pages' => ['view_reports_page'],
            'custom' => ['export_reports'],
        ],
    ]])->assertRedirect();

    $role = Role::findByName('editor', 'web');

    expect($role->permissions->pluck('name')->sort()->values()->all())->toBe(['export_reports', 'update_post', 'view_any_post', 'view_reports_page'])
        ->and(Permission::query()->where('name', 'delete_everything')->exists())->toBeFalse();
});

it('fills and saves the permissions of an existing role', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    $role = Role::create(['name' => 'writer', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::create(['name' => 'view_any_post', 'guard_name' => 'web']));
    // A permission of no resource, page or widget shows under custom permissions.
    $role->givePermissionTo(Permission::create(['name' => 'manage_billing', 'guard_name' => 'web']));

    $page = $this->inertiaProps("/shield/shield/roles/{$role->id}/edit")['page'];
    $state = $page['content'][0]['state'];

    expect($state['name'])->toBe('writer')
        ->and($state['permissions']['resource_post'])->toBe(['view_any_post'])
        ->and($state['permissions']['custom'])->toBe(['manage_billing'])
        ->and($state['permissions']['pages'])->toBe([])
        ->and($state['select_all'])->toBeFalse();

    $this->runAction('/shield/_alfakit/actions', ['token' => $page['token'], 'action' => 'save', 'data' => [
        'name' => 'author',
        'guard_name' => 'web',
        'permissions' => ['resource_post' => ['create_post'], 'resource_category' => ['publish_category'], 'custom' => []],
    ]])->assertRedirect();

    expect($role->fresh()->name)->toBe('author')
        ->and($role->fresh()->permissions->pluck('name')->sort()->values()->all())->toBe(['create_post', 'publish_category']);
});

it('validates that role names are unique per guard', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    Role::create(['name' => 'editor', 'guard_name' => 'web']);
    $token = $this->inertiaProps('/shield/shield/roles/create')['page']['token'];

    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => ['name' => 'editor', 'guard_name' => 'web']])
        ->assertJsonValidationErrors(['name']);

    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'create', 'data' => ['name' => 'editor', 'guard_name' => 'api']])
        ->assertRedirect();
});

it('selects every permission with the select all toggle', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    $token = $this->inertiaProps('/shield/shield/roles/create')['page']['token'];

    $response = $this->postJson('/shield/_alfakit/schema/refresh', [
        'token' => $token,
        'component' => 'form',
        'path' => 'select_all',
        'state' => ['name' => null, 'guard_name' => 'web', 'select_all' => true, 'permissions' => []],
    ])->assertOk();

    expect($response->json('state.permissions.resource_post'))->toHaveCount(12)
        ->and($response->json('state.permissions.pages'))->toBe(['view_reports_page'])
        ->and($response->json('state.permissions.custom'))->toBe(['export_reports']);

    $response = $this->postJson('/shield/_alfakit/schema/refresh', [
        'token' => $token,
        'component' => 'form',
        'path' => 'select_all',
        'state' => ['name' => null, 'guard_name' => 'web', 'select_all' => false, 'permissions' => ['pages' => ['view_reports_page']]],
    ])->assertOk();

    expect($response->json('state.permissions.pages'))->toBe([]);
});

it('marks select all on roles that have every permission', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    $role = Role::query()->create(['name' => 'everything', 'guard_name' => 'web']);
    RoleResource::syncRolePermissions($role, collect(RoleResource::getPermissionOptions(Shield::registry(alfakit()->getPanel('shield'))))->flatMap(fn (array $options): array => array_keys($options))->all());

    $state = $this->inertiaProps("/shield/shield/roles/{$role->id}/edit")['page']['content'][0]['state'];

    expect($state['select_all'])->toBeTrue();
});

it('lists and deletes roles', function (): void {
    $this->actingAs($this->makeUser(['super_admin' => []]));
    $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

    $page = $this->inertiaProps('/shield/shield/roles')['page'];
    $table = collect(shieldFlatten($page['content']))->firstWhere('type', 'Table');

    expect(collect($table['records'])->pluck('key')->all())->toContain($role->id);

    $this->runAction('/shield/_alfakit/actions', ['token' => $page['token'], 'component' => 'table', 'action' => 'delete', 'record' => $role->id])
        ->assertRedirect();

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});

it('protects the role resource with the generated role policy', function (): void {
    $this->generateAndRegisterPolicies();

    $this->actingAs($this->makeUser(['manager' => ['view_any_role', 'view_role']]));

    $this->inertiaGet('/shield/shield/roles')->assertOk();
    $this->inertiaGet('/shield/shield/roles/create')->assertForbidden();
});
