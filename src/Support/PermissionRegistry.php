<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Support;

use AlfaSix\Alfakit\Pages\Page;
use AlfaSix\Alfakit\Panel;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Widgets\Widget;
use AlfaSix\AlfakitShield\Contracts\HasShieldPermissions;
use AlfaSix\AlfakitShield\ShieldPlugin;
use Illuminate\Support\Str;

/**
 * Everything a panel's roles can be given: the permissions of its resources, pages and
 * widgets, plus the plugin's custom permissions.
 */
final class PermissionRegistry
{
    /** @var array<string, array{resource: class-string<resource>, model: string, label: string, permissions: array<string, string>}>|null */
    private ?array $resources = null;

    public function __construct(
        private readonly ShieldPlugin $plugin,
        private readonly Panel $panel,
    ) {}

    public function getPlugin(): ShieldPlugin
    {
        return $this->plugin;
    }

    public function getPanel(): Panel
    {
        return $this->panel;
    }

    public function getGuard(): string
    {
        return $this->plugin->getGuard($this->panel);
    }

    // ---------------------------------------------------------------- Resources

    /**
     * Resources by subject (`post`), each with its permissions (name => label). Resources
     * sharing a model share their permissions (and policy), so only the first is listed.
     *
     * @return array<string, array{resource: class-string<resource>, model: string, label: string, permissions: array<string, string>}>
     */
    public function resources(): array
    {
        if ($this->resources !== null) {
            return $this->resources;
        }

        $resources = [];

        foreach ($this->panel->getResources() as $resource) {
            if (in_array($resource, $this->plugin->getExcludedResources(), true)) {
                continue;
            }

            $subject = self::resourceSubject($resource);

            if (isset($resources[$subject])) {
                continue;
            }

            $permissions = [];

            foreach ($this->resourcePrefixes($resource) as $prefix) {
                $permissions[$this->resourcePermission($resource, $prefix)] = $this->prefixLabel($prefix);
            }

            $resources[$subject] = [
                'resource' => $resource,
                'model' => $resource::getModel(),
                'label' => $resource::getTitleCasePluralModelLabel(),
                'permissions' => $permissions,
            ];
        }

        return $this->resources = $resources;
    }

    /**
     * @param  class-string<resource>  $resource
     * @return list<string>
     */
    public function resourcePrefixes(string $resource): array
    {
        if (is_subclass_of($resource, HasShieldPermissions::class)) {
            return array_values(array_unique($resource::getPermissionPrefixes()));
        }

        return $this->plugin->getResourcePermissionPrefixes();
    }

    /**
     * @param  class-string<resource>  $resource
     */
    public function resourcePermission(string $resource, string $prefix): string
    {
        return $this->plugin->buildPermissionKey($prefix, self::resourceSubject($resource), 'resource', $resource);
    }

    /**
     * The subject of a resource's permissions: its model in snake case (`blog_post`).
     *
     * @param  class-string<resource>  $resource
     */
    public static function resourceSubject(string $resource): string
    {
        return Str::snake(class_basename($resource::getModel()));
    }

    // ---------------------------------------------------------------- Pages and widgets

    /**
     * @return array<class-string<Page>, array{permission: string, label: string}>
     */
    public function pages(): array
    {
        $pages = [];

        foreach ($this->panel->getPages() as $page) {
            if (in_array($page, $this->plugin->getExcludedPages(), true)) {
                continue;
            }

            $pages[$page] = ['permission' => $this->pagePermission($page), 'label' => \AlfaSix\Alfakit\Support\Html::toText($page::getNavigationLabel())];
        }

        return $pages;
    }

    /**
     * @return array<class-string<Widget>, array{permission: string, label: string}>
     */
    public function widgets(): array
    {
        $widgets = [];

        foreach ($this->panel->getWidgets() as $widget) {
            if (in_array($widget, $this->plugin->getExcludedWidgets(), true)) {
                continue;
            }

            $widgets[$widget] = ['permission' => $this->widgetPermission($widget), 'label' => Str::headline(class_basename($widget))];
        }

        return $widgets;
    }

    /**
     * @param  class-string  $page
     */
    public function pagePermission(string $page): string
    {
        return $this->plugin->buildPermissionKey($this->plugin->getPagePermissionPrefix(), self::classSubject($page), 'page', $page);
    }

    /**
     * @param  class-string  $widget
     */
    public function widgetPermission(string $widget): string
    {
        return $this->plugin->buildPermissionKey($this->plugin->getWidgetPermissionPrefix(), self::classSubject($widget), 'widget', $widget);
    }

    /**
     * @param  class-string  $class
     */
    public static function classSubject(string $class): string
    {
        return Str::snake(class_basename($class));
    }

    // ---------------------------------------------------------------- Custom

    /**
     * The plugin's custom permissions (name => label).
     *
     * @return array<string, string>
     */
    public function customPermissions(): array
    {
        $permissions = [];

        foreach ($this->plugin->getCustomPermissions() as $name => $label) {
            $permissions[$name] = $this->customLabel($name, $label);
        }

        return $permissions;
    }

    /**
     * Custom permissions plus permissions of the guard in the database that belong to no
     * resource, page or widget (created by hand or by another tool).
     *
     * @return array<string, string>
     */
    public function customPermissionsWithStored(): array
    {
        $permissions = $this->customPermissions();
        $known = array_flip($this->entityPermissions());
        $model = PermissionModels::permission();

        foreach ($model::query()->where('guard_name', $this->getGuard())->orderBy('name')->pluck('name') as $name) {
            $name = (string) $name;

            if (! isset($known[$name]) && ! isset($permissions[$name])) {
                $permissions[$name] = $this->customLabel($name, null);
            }
        }

        return $permissions;
    }

    // ---------------------------------------------------------------- Totals

    /**
     * The permissions of every resource, page and widget.
     *
     * @return list<string>
     */
    public function entityPermissions(): array
    {
        $names = [];

        foreach ($this->resources() as $resource) {
            array_push($names, ...array_keys($resource['permissions']));
        }

        foreach ([...$this->pages(), ...$this->widgets()] as $entry) {
            $names[] = $entry['permission'];
        }

        return array_values(array_unique($names));
    }

    /**
     * Every permission the panel defines (entities and custom permissions).
     *
     * @return list<string>
     */
    public function allPermissions(): array
    {
        return array_values(array_unique([...$this->entityPermissions(), ...array_keys($this->customPermissions())]));
    }

    // ---------------------------------------------------------------- Labels

    public function prefixLabel(string $prefix): string
    {
        if ($this->plugin->shouldLocalizePermissionLabels()) {
            $key = "alfakit-shield::shield.prefixes.{$prefix}";
            $label = __($key);

            if (is_string($label) && $label !== $key) {
                return $label;
            }
        }

        return Str::headline($prefix);
    }

    private function customLabel(string $name, ?string $label): string
    {
        if ($this->plugin->shouldLocalizePermissionLabels()) {
            $key = "alfakit-shield::shield.custom.{$name}";
            $translated = __($key);

            if (is_string($translated) && $translated !== $key) {
                return $translated;
            }
        }

        return $label ?? Str::headline($name);
    }
}
