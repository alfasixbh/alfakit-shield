<?php

declare(strict_types=1);

use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\AlfakitShield\Resources\RoleResource;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionRegistry;
use AlfaSix\AlfakitShield\Tests\Fixtures\ReportsPage;
use AlfaSix\AlfakitShield\Tests\Fixtures\SalesWidget;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldCategoryResource;
use AlfaSix\AlfakitShield\Tests\Fixtures\ShieldPostResource;

uses(AlfaSix\AlfakitShield\Tests\TestCase::class);

it('registers the plugin and the role resource on the panel', function (): void {
    $panel = alfakit()->getPanel('shield');

    expect($panel->hasPlugin(ShieldPlugin::ID))->toBeTrue()
        ->and(ShieldPlugin::get($panel))->toBeInstanceOf(ShieldPlugin::class)
        ->and($panel->getResources())->toContain(RoleResource::class)
        ->and(ShieldPlugin::get(alfakit()->getPanel('admin')))->toBeNull();
});

it('places the role resource in the configured navigation group', function (): void {
    $this->actingAs($this->makeUser());

    $navigation = $this->inertiaProps('/shield')['alfakit']['navigation'];
    $settings = collect($navigation)->firstWhere('label', 'Settings');

    expect($settings['items'][0])->toMatchArray(['label' => 'Roles', 'icon' => 'shield-check', 'url' => 'http://localhost/shield/shield/roles']);
});

it('can register no role resource', function (): void {
    $panel = AlfaSix\Alfakit\Panel::make('other')->plugin(ShieldPlugin::make()->roleResource(false));

    expect($panel->getResources())->not->toContain(RoleResource::class);
});

it('discovers the permissions of resources, pages and widgets', function (): void {
    $panel = alfakit()->getPanel('shield');
    $registry = ShieldPlugin::get($panel)->getPermissionRegistry($panel);

    expect(array_keys($registry->resources()))->toBe(['post', 'category', 'role'])
        ->and(array_keys($registry->resources()['post']['permissions']))->toBe([
            'view_any_post', 'view_post', 'create_post', 'update_post', 'delete_post', 'delete_any_post',
            'restore_post', 'restore_any_post', 'force_delete_post', 'force_delete_any_post', 'replicate_post', 'reorder_post',
        ])
        // `HasShieldPermissions` resources declare their own prefixes.
        ->and(array_keys($registry->resources()['category']['permissions']))->toBe(['view_any_category', 'view_category', 'create_category', 'update_category', 'publish_category'])
        ->and($registry->resources()['post']['resource'])->toBe(ShieldPostResource::class)
        ->and($registry->pages())->toBe([ReportsPage::class => ['permission' => 'view_reports_page', 'label' => 'Reports']])
        ->and($registry->widgets())->toBe([SalesWidget::class => ['permission' => 'view_sales_widget', 'label' => 'Sales Widget']])
        ->and($registry->customPermissions())->toBe(['export_reports' => 'Export reports'])
        ->and($registry->allPermissions())->toContain('view_any_role', 'publish_category', 'view_reports_page', 'view_sales_widget', 'export_reports')
        ->and($registry->pages())->not->toHaveKey(Dashboard::class);
});

it('customises prefixes, permission keys, labels and exclusions', function (): void {
    $panel = alfakit()->getPanel('shield');

    $plugin = ShieldPlugin::make()
        ->resourcePermissionPrefixes(['view_any', 'create'])
        ->pagePermissionPrefix('access')
        ->widgetPermissionPrefix('see')
        ->buildPermissionKeyUsing(fn (string $prefix, string $subject): string => "{$subject}.{$prefix}")
        ->excludeResources([ShieldCategoryResource::class])
        ->excludeWidgets([SalesWidget::class])
        ->customPermissions(['impersonate']);

    $registry = new PermissionRegistry($plugin, $panel);

    expect(array_keys($registry->resources()))->toBe(['post', 'role'])
        ->and($registry->resources()['post']['permissions'])->toBe(['post.view_any' => 'View Any', 'post.create' => 'Create'])
        ->and($registry->pagePermission(ReportsPage::class))->toBe('reports_page.access')
        ->and($registry->widgets())->toBe([])
        ->and($registry->customPermissions())->toBe(['impersonate' => 'Impersonate']);
});

it('localizes permission labels', function (): void {
    $panel = alfakit()->getPanel('shield');
    $registry = new PermissionRegistry(ShieldPlugin::make()->localizePermissionLabels(), $panel);

    app('translator')->addLines(['shield.prefixes.view_any' => 'Alle ansehen'], 'de', 'alfakit-shield');
    app()->setLocale('de');

    expect($registry->resources()['post']['permissions']['view_any_post'])->toBe('Alle ansehen')
        // Prefixes without a translation fall back to their headline.
        ->and($registry->resources()['category']['permissions']['publish_category'])->toBe('Publish');
});

it('reads the layout options', function (): void {
    $plugin = ShieldPlugin::make()
        ->gridColumns(2)
        ->sectionColumnSpan('full')
        ->checkboxListColumns(4)
        ->resourceCheckboxListColumns(['default' => 1, 'lg' => 2])
        ->superAdmin(role: 'owner', gate: false)
        ->panelUser(role: 'member');

    expect($plugin->getGridColumns())->toBe(2)
        ->and($plugin->getSectionColumnSpan())->toBe('full')
        ->and($plugin->getCheckboxListColumns())->toBe(4)
        ->and($plugin->getResourceCheckboxListColumns())->toBe(['default' => 1, 'lg' => 2])
        ->and($plugin->getSuperAdminRole())->toBe('owner')
        ->and($plugin->definesSuperAdminViaGate())->toBeFalse()
        ->and($plugin->isPanelUserEnabled())->toBeTrue()
        ->and($plugin->getPanelUserRole())->toBe('member');
});
