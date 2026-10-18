<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Tests\Fixtures\Models\Post;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Tests\Fixtures\ReportsPage;
use AlfaSix\AlfakitShield\Tests\Fixtures\SalesWidget;
use Spatie\Permission\Models\Role;

uses(AlfaSix\AlfakitShield\Tests\TestCase::class);

function shieldNavigationLabels(array $props): array
{
    return collect($props['alfakit']['navigation'])->flatMap(fn (array $group): array => array_column($group['items'], 'label'))->all();
}

function shieldWidgetIds(array $props): array
{
    return collect($props['page']['content'])->flatMap(fn (array $block): array => array_column($block['widgets'] ?? [], 'id'))->all();
}

it('grants access through the permissions of the user roles', function (): void {
    $this->generateAndRegisterPolicies();
    $post = Post::query()->create(['title' => 'Hello', 'slug' => 'hello']);

    $this->actingAs($this->makeUser(['editor' => ['view_any_post', 'update_post']]));

    $this->inertiaGet('/shield/posts')->assertOk();
    $this->inertiaGet("/shield/posts/{$post->id}/edit")->assertOk();
    $this->inertiaGet('/shield/posts/create')->assertForbidden();
    $this->inertiaGet('/shield/categories')->assertForbidden();
    $this->inertiaGet('/shield/shield/roles')->assertForbidden();

    $labels = shieldNavigationLabels($this->inertiaProps('/shield'));

    expect($labels)->toContain('Posts')
        ->and(array_intersect(['Categories', 'Roles'], $labels))->toBe([]);
});

it('denies actions through the internal endpoint without the permission', function (): void {
    $this->generateAndRegisterPolicies();
    $post = Post::query()->create(['title' => 'Hello', 'slug' => 'hello']);

    $this->actingAs($this->makeUser(['viewer' => ['view_any_post', 'view_post', 'update_post']]));
    $token = $this->inertiaProps("/shield/posts/{$post->id}/edit")['page']['token'];

    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'save', 'data' => ['title' => 'Changed', 'slug' => 'hello']])
        ->assertRedirect();

    expect($post->fresh()?->getAttribute('title'))->toBe('Changed');

    // Revoke the update permission: the same save is refused.
    Role::findByName('viewer', 'web')->revokePermissionTo('update_post');

    $this->runAction('/shield/_alfakit/actions', ['token' => $token, 'action' => 'save', 'data' => ['title' => 'Again', 'slug' => 'hello']])
        ->assertForbidden();

    expect($post->fresh()?->getAttribute('title'))->toBe('Changed');
});

it('lets super admins through the gate without permissions', function (): void {
    $this->generateAndRegisterPolicies();

    $this->actingAs($this->makeUser(['super_admin' => []]));

    $this->inertiaGet('/shield/posts/create')->assertOk();
    $this->inertiaGet('/shield/shield/roles')->assertOk();
    $this->inertiaGet('/shield/reports')->assertOk();

    expect(shieldWidgetIds($this->inertiaProps('/shield')))->toContain(SalesWidget::idFromClass(SalesWidget::class));
});

it('does not use the gate when the super admin role holds the permissions itself', function (): void {
    $this->generateAndRegisterPolicies();
    ShieldPlugin::get(alfakit()->getPanel('shield'))->superAdmin(gate: false);

    $this->actingAs($this->makeUser(['super_admin' => []]));

    $this->inertiaGet('/shield/posts/create')->assertForbidden();
});

it('assigns the super admin role with the command', function (): void {
    $this->generateAndRegisterPolicies();
    $user = $this->makeUser();

    $this->artisan('alfakit-shield:super-admin', ['--user' => $user->id, '--panel' => 'shield'])->assertSuccessful();

    expect($user->fresh()->hasRole('super_admin'))->toBeTrue();

    $this->actingAs($user->fresh());
    $this->inertiaGet('/shield/posts/create')->assertOk();
});

it('gives a gate-less super admin every permission with the command', function (): void {
    ShieldPlugin::get(alfakit()->getPanel('shield'))->superAdmin(gate: false);
    $user = $this->makeUser();

    $this->artisan('alfakit-shield:super-admin', ['--user' => $user->id, '--panel' => 'shield'])->assertSuccessful();

    expect($user->fresh()->can('force_delete_any_post'))->toBeTrue()
        ->and($user->fresh()->can('view_reports_page'))->toBeTrue();
});

it('fails the super admin command for unknown users and panels without the plugin', function (): void {
    $this->makeUser();
    $this->makeUser(email: 'other@example.com');

    $this->artisan('alfakit-shield:super-admin', ['--user' => 999, '--panel' => 'shield'])->assertFailed();
    $this->artisan('alfakit-shield:super-admin', ['--user' => 1, '--panel' => 'admin'])->assertFailed();
});

it('hides pages without their permission', function (): void {
    $this->actingAs($this->makeUser());

    $this->inertiaGet('/shield/reports')->assertForbidden();
    expect(shieldNavigationLabels($this->inertiaProps('/shield')))->not->toContain('Reports')
        ->and(ReportsPage::getShieldPermission())->toBe('view_reports_page');
});

it('shows pages with their permission', function (): void {
    $this->actingAs($this->makeUser(['analyst' => ['view_reports_page']]));

    $this->inertiaGet('/shield/reports')->assertOk();
    expect(shieldNavigationLabels($this->inertiaProps('/shield')))->toContain('Reports');
});

it('hides widgets without their permission', function (): void {
    $this->actingAs($this->makeUser());

    expect(shieldWidgetIds($this->inertiaProps('/shield')))->not->toContain(SalesWidget::idFromClass(SalesWidget::class));

    auth()->logout();
    $this->actingAs($this->makeUser(['sales' => ['view_sales_widget']], 'sales@example.com'));

    expect(shieldWidgetIds($this->inertiaProps('/shield')))->toContain(SalesWidget::idFromClass(SalesWidget::class));
});

it('requires the panel user role when it is enabled', function (): void {
    ShieldPlugin::get(alfakit()->getPanel('shield'))->panelUser();

    // New users get the panel user role (HasPanelShield).
    $user = $this->makeUser();
    expect($user->hasRole('panel_user'))->toBeTrue()
        ->and($user->canAccessPanel(alfakit()->getPanel('shield')))->toBeTrue();

    $user->removeRole('panel_user');
    expect($user->fresh()->canAccessPanel(alfakit()->getPanel('shield')))->toBeFalse();

    $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    expect($user->fresh()->canAccessPanel(alfakit()->getPanel('shield')))->toBeTrue();
});
