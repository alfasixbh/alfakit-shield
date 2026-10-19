# AlfaKit Shield

<p><picture><source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/alfasixbh/alfakit/main/art/alfasix/alfa-six-micro-white.svg"><img src="https://raw.githubusercontent.com/alfasixbh/alfakit/main/art/alfasix/alfa-six-micro-navy.svg" alt="" width="24" height="18" align="top"></picture> <sub>by <a href="https://alfasixbh.com">Alfa Six</a></sub></p>

> This is a read-only split of the [AlfaKit monorepo](https://github.com/alfasixbh/alfakit/tree/main/packages/plugins/shield). Please open issues and pull requests there.

Roles and permissions for [AlfaKit](https://alfakit.dev) panels, built on [spatie/laravel-permission](https://spatie.be/docs/laravel-permission). The AlfaKit counterpart of Filament Shield:

- a **Roles** resource whose form lists every permission of the panel in tabs (resources, pages, widgets, custom permissions), with a "select all" toggle;
- **permission discovery** from the panel's resources, pages and widgets (`view_any_post`, `view_reports_page`…);
- `alfakit-shield:generate` to create the permissions and **policy classes** that check them;
- a **super admin** role that passes every check, and an optional **panel user** role;
- `HasPageShield` / `HasWidgetShield` traits to guard pages and widgets;
- **tenant-scoped roles** through spatie teams on panels with tenancy.

It renders with the React, Vue and Svelte renderers; it ships no frontend code.

## Installation

```bash
composer require alfasix/alfakit-shield
php artisan alfakit-shield:install --migrate
```

`alfakit-shield:install` publishes spatie's `config/permission.php` and migration and `config/alfakit-shield.php`. Pass `--teams` to turn on spatie teams (tenant-scoped roles), `--migrate` to run the migrations and `--generate` to generate permissions and policies right away.

Add spatie's `HasRoles` trait to your user model (and, optionally, `HasPanelShield`):

```php
use AlfaSix\AlfakitShield\Traits\HasPanelShield;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements CanAccessPanel
{
    use HasPanelShield;
    use HasRoles;
}
```

## Register

```php
use AlfaSix\AlfakitShield\ShieldPlugin;

$panel->plugin(
    ShieldPlugin::make()
        ->navigationGroup('Settings')
        ->gridColumns(['default' => 1, 'sm' => 2, 'lg' => 3])
        ->sectionColumnSpan(1)
        ->checkboxListColumns(['default' => 1, 'sm' => 2, 'lg' => 4])
        ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
);
```

Then create the permissions and policies, and make yourself super admin:

```bash
php artisan alfakit-shield:generate --panel=admin
php artisan alfakit-shield:super-admin --user=1 --panel=admin
```

## Usage

### Permissions

Each resource gets one permission per prefix, `{prefix}_{model}`: `view_any_post`, `view_post`, `create_post`, `update_post`, `delete_post`, `delete_any_post`, `restore_post`, `restore_any_post`, `force_delete_post`, `force_delete_any_post`, `replicate_post`, `reorder_post`. Pages and widgets get `view_{class}` (`view_reports_page`, `view_sales_widget`).

```php
ShieldPlugin::make()
    ->resourcePermissionPrefixes(['view_any', 'view', 'create', 'update', 'delete'])
    ->pagePermissionPrefix('view')
    ->widgetPermissionPrefix('view')
    ->buildPermissionKeyUsing(fn (string $prefix, string $subject) => "{$subject}.{$prefix}")
    ->customPermissions(['export_orders' => 'Export orders'])
    ->excludeResources([AuditResource::class])
    ->excludePages([Settings::class])
    ->excludeWidgets([AccountWidget::class])
    ->localizePermissionLabels();
```

A resource can choose its own prefixes:

```php
use AlfaSix\AlfakitShield\Contracts\HasShieldPermissions;

class PostResource extends Resource implements HasShieldPermissions
{
    public static function getPermissionPrefixes(): array
    {
        return [...ShieldPlugin::DEFAULT_RESOURCE_PREFIXES, 'publish'];
    }
}
```

### Policies

`alfakit-shield:generate` writes `app/Policies/{Model}Policy.php` for each resource model, one method per prefix (`viewAny()` checks `view_any_post`…). Existing policies are skipped unless you pass `--force`. `--option=permissions|policies|policies_and_permissions` chooses what to generate, `--resource=PostResource` limits it to some resources.

### Pages and widgets

```php
use AlfaSix\AlfakitShield\Traits\HasPageShield;

class Reports extends Page
{
    use HasPageShield; // requires view_reports
}

class SalesChart extends ChartWidget
{
    use HasWidgetShield; // requires view_sales_chart
}
```

### Super admin and panel user

```php
ShieldPlugin::make()
    ->superAdmin(role: 'super_admin', gate: true) // Gate::before(): passes every check
    ->panelUser(role: 'panel_user');              // given to new users, required by canAccessPanel()
```

With `gate: false` the super admin role holds every permission instead (`generate` and `super-admin` keep it in sync).

### Tenancy

On a panel with `->tenant(...)` and `permission.teams` on, roles belong to the current tenant: the role resource lists and creates the tenant's roles, and permission checks use the tenant as spatie's team. `alfakit-shield:super-admin --tenant=1` assigns the super admin role within a tenant. Turn it off with `->scopeToTenant(false)`.

See the [documentation](https://alfakit.dev/docs/plugins/shield) for every option.

## Testing

```bash
vendor/bin/pest --test-directory=packages/plugins packages/plugins/shield
```

## License

MIT. See [LICENSE.md](LICENSE.md).
