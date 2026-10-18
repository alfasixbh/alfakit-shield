<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield;

use AlfaSix\Alfakit\Contracts\Plugin;
use AlfaSix\Alfakit\Pages\Dashboard;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Support\Concerns\EvaluatesClosures;
use AlfaSix\AlfakitShield\Resources\RoleResource;
use AlfaSix\AlfakitShield\Support\PermissionRegistry;
use Closure;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role and permission management for a panel, built on spatie/laravel-permission.
 *
 * ```php
 * $panel->plugin(ShieldPlugin::make()->navigationGroup('Settings')->superAdmin(role: 'admin'));
 * ```
 */
class ShieldPlugin implements Plugin
{
    use EvaluatesClosures;

    public const ID = 'alfakit-shield';

    /** The prefixes of every resource permission, in the order they are listed in the role form. */
    public const DEFAULT_RESOURCE_PREFIXES = [
        'view_any',
        'view',
        'create',
        'update',
        'delete',
        'delete_any',
        'restore',
        'restore_any',
        'force_delete',
        'force_delete_any',
        'replicate',
        'reorder',
    ];

    /** @var class-string<resource>|false */
    protected string|false $roleResource = RoleResource::class;

    /** @var array<string, int|null>|int */
    protected array|int $gridColumns = ['default' => 1, 'sm' => 2, 'lg' => 3];

    /** @var array<string, int|string|null>|int|string */
    protected array|int|string $sectionColumnSpan = 1;

    /** @var array<string, int|null>|int */
    protected array|int $checkboxListColumns = ['default' => 1, 'sm' => 2, 'lg' => 3];

    /** @var array<string, int|null>|int */
    protected array|int $resourceCheckboxListColumns = ['default' => 1, 'sm' => 2];

    protected string|Closure|null $navigationGroup = null;

    protected int|Closure|null $navigationSort = null;

    protected string|Closure|null $navigationIcon = 'shield-check';

    protected string|Closure|null $navigationLabel = null;

    protected bool|Closure $shouldRegisterNavigation = true;

    protected string|Closure|null $navigationBadge = null;

    protected bool $isSuperAdminEnabled = true;

    protected string $superAdminRole = 'super_admin';

    protected bool $definesSuperAdminViaGate = true;

    protected bool $isPanelUserEnabled = false;

    protected string $panelUserRole = 'panel_user';

    /** @var list<string> */
    protected array $resourcePermissionPrefixes = self::DEFAULT_RESOURCE_PREFIXES;

    protected string $pagePermissionPrefix = 'view';

    protected string $widgetPermissionPrefix = 'view';

    protected ?Closure $buildPermissionKeyUsing = null;

    protected bool $shouldLocalizePermissionLabels = false;

    /** @var array<class-string> */
    protected array $excludedResources = [];

    /** @var array<class-string> */
    protected array $excludedPages = [Dashboard::class];

    /** @var array<class-string> */
    protected array $excludedWidgets = [];

    /** @var array<string, string|null> permission name => label */
    protected array $customPermissions = [];

    protected bool $hasResourcesTab = true;

    protected bool $hasPagesTab = true;

    protected bool $hasWidgetsTab = true;

    protected bool $hasCustomPermissionsTab = true;

    protected ?bool $isScopedToTenant = null;

    protected ?string $guard = null;

    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * The plugin registered on the given panel (default: the current or default panel), or
     * null when that panel doesn't use Shield.
     */
    public static function get(?Panel $panel = null): ?static
    {
        $panel ??= alfakit()->getCurrentPanel() ?? alfakit()->getDefaultPanel();

        if ($panel === null || ! $panel->hasPlugin(self::ID)) {
            return null;
        }

        $plugin = $panel->getPlugin(self::ID);

        return $plugin instanceof static ? $plugin : null;
    }

    public function getId(): string
    {
        return self::ID;
    }

    protected function getEvaluationIdentifier(): string
    {
        return 'plugin';
    }

    public function register(Panel $panel): void
    {
        if ($this->roleResource !== false) {
            $panel->resources([$this->roleResource]);
        }

        $this->configureTeamModel($panel);
    }

    public function boot(Panel $panel): void
    {
        $this->configureTeamModel($panel);
    }

    /**
     * spatie needs `permission.models.team` with teams on: default it to the panel's
     * tenant model.
     */
    protected function configureTeamModel(Panel $panel): void
    {
        if (! $this->isScopedToTenant($panel)) {
            return;
        }

        $registrar = app(PermissionRegistrar::class);

        if ($registrar->getTeamClass() === null) {
            $registrar->setTeamClass($panel->getTenantModel());
        }
    }

    // ---------------------------------------------------------------- Role resource

    /**
     * Use your own `RoleResource` subclass, or `false` to register no role resource.
     *
     * @param  class-string<resource>|false  $resource
     */
    public function roleResource(string|false $resource): static
    {
        if ($resource !== false && ! is_subclass_of($resource, Resource::class)) {
            throw new InvalidArgumentException("[{$resource}] is not a resource class.");
        }

        $this->roleResource = $resource;

        return $this;
    }

    /**
     * @return class-string<resource>|false
     */
    public function getRoleResource(): string|false
    {
        return $this->roleResource;
    }

    // ---------------------------------------------------------------- Layout

    /**
     * Columns of the grid of resource sections (a number or a breakpoint map).
     *
     * @param  array<string, int|null>|int  $columns
     */
    public function gridColumns(array|int $columns): static
    {
        $this->gridColumns = $columns;

        return $this;
    }

    /**
     * @return array<string, int|null>|int
     */
    public function getGridColumns(): array|int
    {
        return $this->gridColumns;
    }

    /**
     * Column span of each resource section in that grid.
     *
     * @param  array<string, int|string|null>|int|string  $span
     */
    public function sectionColumnSpan(array|int|string $span): static
    {
        $this->sectionColumnSpan = $span;

        return $this;
    }

    /**
     * @return array<string, int|string|null>|int|string
     */
    public function getSectionColumnSpan(): array|int|string
    {
        return $this->sectionColumnSpan;
    }

    /**
     * Columns of the page, widget and custom permission checkbox lists.
     *
     * @param  array<string, int|null>|int  $columns
     */
    public function checkboxListColumns(array|int $columns): static
    {
        $this->checkboxListColumns = $columns;

        return $this;
    }

    /**
     * @return array<string, int|null>|int
     */
    public function getCheckboxListColumns(): array|int
    {
        return $this->checkboxListColumns;
    }

    /**
     * Columns of the checkbox list inside each resource section.
     *
     * @param  array<string, int|null>|int  $columns
     */
    public function resourceCheckboxListColumns(array|int $columns): static
    {
        $this->resourceCheckboxListColumns = $columns;

        return $this;
    }

    /**
     * @return array<string, int|null>|int
     */
    public function getResourceCheckboxListColumns(): array|int
    {
        return $this->resourceCheckboxListColumns;
    }

    /**
     * Show or hide the tabs of the role form. Empty tabs are always hidden.
     */
    public function tabs(bool $resources = true, bool $pages = true, bool $widgets = true, bool $customPermissions = true): static
    {
        $this->hasResourcesTab = $resources;
        $this->hasPagesTab = $pages;
        $this->hasWidgetsTab = $widgets;
        $this->hasCustomPermissionsTab = $customPermissions;

        return $this;
    }

    public function hasTab(string $tab): bool
    {
        return match ($tab) {
            'resources' => $this->hasResourcesTab,
            'pages' => $this->hasPagesTab,
            'widgets' => $this->hasWidgetsTab,
            'custom' => $this->hasCustomPermissionsTab,
            default => false,
        };
    }

    // ---------------------------------------------------------------- Navigation

    public function navigationGroup(string|Closure|null $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    public function getNavigationGroup(): ?string
    {
        $group = $this->evaluate($this->navigationGroup);

        return is_string($group) ? $group : null;
    }

    public function navigationSort(int|Closure|null $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    public function getNavigationSort(): ?int
    {
        $sort = $this->evaluate($this->navigationSort);

        return is_int($sort) ? $sort : null;
    }

    public function navigationIcon(string|Closure|null $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    public function getNavigationIcon(): ?string
    {
        $icon = $this->evaluate($this->navigationIcon);

        return is_string($icon) ? $icon : null;
    }

    public function navigationLabel(string|Closure|null $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    public function getNavigationLabel(): ?string
    {
        $label = $this->evaluate($this->navigationLabel);

        return is_string($label) ? $label : null;
    }

    public function registerNavigation(bool|Closure $condition = true): static
    {
        $this->shouldRegisterNavigation = $condition;

        return $this;
    }

    public function shouldRegisterNavigation(): bool
    {
        return (bool) $this->evaluate($this->shouldRegisterNavigation);
    }

    /**
     * A navigation badge for the role resource, e.g. `fn () => Role::count()`.
     */
    public function navigationBadge(string|Closure|null $badge): static
    {
        $this->navigationBadge = $badge;

        return $this;
    }

    public function getNavigationBadge(): string|int|null
    {
        $badge = $this->evaluate($this->navigationBadge);

        return is_string($badge) || is_int($badge) ? $badge : null;
    }

    // ---------------------------------------------------------------- Roles

    /**
     * The role that may do everything. With `gate: true` it is granted through
     * `Gate::before()`; with `gate: false` it is given every permission instead
     * (`alfakit-shield:generate` and `alfakit-shield:super-admin` keep it in sync).
     */
    public function superAdmin(bool $enabled = true, string $role = 'super_admin', bool $gate = true): static
    {
        $this->isSuperAdminEnabled = $enabled;
        $this->superAdminRole = $role;
        $this->definesSuperAdminViaGate = $gate;

        return $this;
    }

    public function isSuperAdminEnabled(): bool
    {
        return $this->isSuperAdminEnabled;
    }

    public function getSuperAdminRole(): string
    {
        return $this->superAdminRole;
    }

    public function definesSuperAdminViaGate(): bool
    {
        return $this->isSuperAdminEnabled && $this->definesSuperAdminViaGate;
    }

    /**
     * A role every user gets when created (with the `HasPanelShield` trait on the user
     * model) and that `canAccessPanel()` requires.
     */
    public function panelUser(bool $enabled = true, string $role = 'panel_user'): static
    {
        $this->isPanelUserEnabled = $enabled;
        $this->panelUserRole = $role;

        return $this;
    }

    public function isPanelUserEnabled(): bool
    {
        return $this->isPanelUserEnabled;
    }

    public function getPanelUserRole(): string
    {
        return $this->panelUserRole;
    }

    /**
     * The guard of the roles and permissions; the panel's auth guard by default.
     */
    public function guard(?string $guard): static
    {
        $this->guard = $guard;

        return $this;
    }

    public function getGuard(Panel $panel): string
    {
        return $this->guard ?? $panel->getAuthGuard();
    }

    // ---------------------------------------------------------------- Permissions

    /**
     * The permission prefixes of every resource (resources implementing
     * `HasShieldPermissions` declare their own).
     *
     * @param  list<string>  $prefixes
     */
    public function resourcePermissionPrefixes(array $prefixes): static
    {
        $this->resourcePermissionPrefixes = array_values(array_unique($prefixes));

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getResourcePermissionPrefixes(): array
    {
        return $this->resourcePermissionPrefixes;
    }

    public function pagePermissionPrefix(string $prefix): static
    {
        $this->pagePermissionPrefix = $prefix;

        return $this;
    }

    public function getPagePermissionPrefix(): string
    {
        return $this->pagePermissionPrefix;
    }

    public function widgetPermissionPrefix(string $prefix): static
    {
        $this->widgetPermissionPrefix = $prefix;

        return $this;
    }

    public function getWidgetPermissionPrefix(): string
    {
        return $this->widgetPermissionPrefix;
    }

    /**
     * Build permission names yourself. The closure receives `$prefix` (`view_any`),
     * `$subject` (`post`), `$entity` (`resource`, `page` or `widget`) and `$class`.
     * The default is `{prefix}_{subject}`.
     */
    public function buildPermissionKeyUsing(?Closure $callback): static
    {
        $this->buildPermissionKeyUsing = $callback;

        return $this;
    }

    /**
     * @param  class-string  $class
     */
    public function buildPermissionKey(string $prefix, string $subject, string $entity, string $class): string
    {
        if ($this->buildPermissionKeyUsing === null) {
            return "{$prefix}_{$subject}";
        }

        return (string) $this->evaluate($this->buildPermissionKeyUsing, [
            'prefix' => $prefix,
            'subject' => $subject,
            'entity' => $entity,
            'class' => $class,
        ]);
    }

    /**
     * Label permissions with translations (`alfakit-shield::shield.prefixes.*`) instead of
     * their headline-cased prefix.
     */
    public function localizePermissionLabels(bool $condition = true): static
    {
        $this->shouldLocalizePermissionLabels = $condition;

        return $this;
    }

    public function shouldLocalizePermissionLabels(): bool
    {
        return $this->shouldLocalizePermissionLabels;
    }

    /**
     * @param  array<class-string>  $resources
     */
    public function excludeResources(array $resources): static
    {
        $this->excludedResources = [...$this->excludedResources, ...$resources];

        return $this;
    }

    /**
     * Pages left out of the permissions (the dashboard is excluded by default).
     *
     * @param  array<class-string>  $pages
     */
    public function excludePages(array $pages): static
    {
        $this->excludedPages = [...$this->excludedPages, ...$pages];

        return $this;
    }

    /**
     * @param  array<class-string>  $widgets
     */
    public function excludeWidgets(array $widgets): static
    {
        $this->excludedWidgets = [...$this->excludedWidgets, ...$widgets];

        return $this;
    }

    /**
     * @return array<class-string>
     */
    public function getExcludedResources(): array
    {
        return $this->excludedResources;
    }

    /**
     * @return array<class-string>
     */
    public function getExcludedPages(): array
    {
        return $this->excludedPages;
    }

    /**
     * @return array<class-string>
     */
    public function getExcludedWidgets(): array
    {
        return $this->excludedWidgets;
    }

    /**
     * Permissions that don't belong to a resource, page or widget: a list of names, or
     * names mapped to labels (`['export_orders' => 'Export orders']`).
     *
     * @param  array<int|string, string>  $permissions
     */
    public function customPermissions(array $permissions): static
    {
        foreach ($permissions as $name => $label) {
            if (is_int($name)) {
                $this->customPermissions[$label] = null;
            } else {
                $this->customPermissions[$name] = $label;
            }
        }

        return $this;
    }

    /**
     * @return array<string, string|null>
     */
    public function getCustomPermissions(): array
    {
        return $this->customPermissions;
    }

    // ---------------------------------------------------------------- Tenancy

    /**
     * Scope roles to the panel's tenant through spatie's teams. By default on when the
     * panel has tenancy and `permission.teams` is enabled.
     */
    public function scopeToTenant(?bool $condition = true): static
    {
        $this->isScopedToTenant = $condition;

        return $this;
    }

    public function isScopedToTenant(Panel $panel): bool
    {
        if (! $panel->hasTenancy() || ! config('permission.teams')) {
            return false;
        }

        return $this->isScopedToTenant ?? true;
    }

    // ---------------------------------------------------------------- Discovery

    public function getPermissionRegistry(Panel $panel): PermissionRegistry
    {
        return new PermissionRegistry($this, $panel);
    }
}
