<?php

declare(strict_types=1);

namespace AlfaSix\AlfakitShield\Resources;

use AlfaSix\Alfakit\Actions\DeleteAction;
use AlfaSix\Alfakit\Actions\DeleteBulkAction;
use AlfaSix\Alfakit\Actions\EditAction;
use AlfaSix\Alfakit\Forms\Components\CheckboxList;
use AlfaSix\Alfakit\Forms\Components\TextInput;
use AlfaSix\Alfakit\Forms\Components\Toggle;
use AlfaSix\Alfakit\Resources\Resource;
use AlfaSix\Alfakit\Schemas\Components\Grid;
use AlfaSix\Alfakit\Schemas\Components\Group;
use AlfaSix\Alfakit\Schemas\Components\Section;
use AlfaSix\Alfakit\Schemas\Components\Tabs;
use AlfaSix\Alfakit\Schemas\Components\Tabs\Tab;
use AlfaSix\Alfakit\Schemas\Components\Utilities\Get;
use AlfaSix\Alfakit\Schemas\Components\Utilities\Set;
use AlfaSix\Alfakit\Schemas\Schema;
use AlfaSix\Alfakit\Tables\Columns\TextColumn;
use AlfaSix\Alfakit\Tables\Table;
use AlfaSix\AlfakitShield\Resources\RoleResource\Pages;
use AlfaSix\AlfakitShield\Shield;
use AlfaSix\AlfakitShield\ShieldPlugin;
use AlfaSix\AlfakitShield\Support\PermissionModels;
use AlfaSix\AlfakitShield\Support\PermissionRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use Spatie\Permission\Models\Role;
use UnitEnum;

/**
 * Lists, creates and edits the roles of a panel and the permissions they grant.
 */
class RoleResource extends Resource
{
    /** The key under which the role form keeps the permission checkbox lists. */
    public const PERMISSIONS_KEY = 'permissions';

    protected static ?string $slug = 'shield/roles';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModel(): string
    {
        return PermissionModels::role();
    }

    public static function getModelLabel(): string
    {
        return __('alfakit-shield::shield.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('alfakit-shield::shield.resource.plural_label');
    }

    // ---------------------------------------------------------------- Navigation

    public static function getNavigationLabel(): string
    {
        return ShieldPlugin::get()?->getNavigationLabel() ?? __('alfakit-shield::shield.resource.navigation_label');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ShieldPlugin::get()?->getNavigationGroup() ?? parent::getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return ShieldPlugin::get()?->getNavigationSort() ?? parent::getNavigationSort();
    }

    public static function getNavigationIcon(): ?string
    {
        return ShieldPlugin::get()?->getNavigationIcon() ?? parent::getNavigationIcon();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (ShieldPlugin::get()?->shouldRegisterNavigation() ?? true) && parent::shouldRegisterNavigation();
    }

    public static function getNavigationBadge(): string|int|null
    {
        return ShieldPlugin::get()?->getNavigationBadge();
    }

    // ---------------------------------------------------------------- Tenancy

    /**
     * Roles belong to the current tenant through spatie's team column, when teams are on.
     */
    public static function isScopedToTenant(): bool
    {
        $panel = alfakit()->getCurrentOrDefaultPanel();

        return ShieldPlugin::get($panel)?->isScopedToTenant($panel) ?? false;
    }

    public static function scopeEloquentQueryToTenant(Builder $query, Model $tenant): Builder
    {
        return $query->where($query->qualifyColumn(PermissionModels::teamForeignKey()), $tenant->getKey());
    }

    // ---------------------------------------------------------------- Form

    public static function form(Schema $schema): Schema
    {
        $registry = Shield::registry();
        $lists = static::getPermissionOptions($registry);

        return $schema->columns(1)->components([
            Section::make()
                ->columns(['default' => 1, 'sm' => 2, 'lg' => 3])
                ->schema([
                    TextInput::make('name')
                        ->label(__('alfakit-shield::shield.fields.name'))
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: static function (Unique $rule, Get $get) use ($registry): Unique {
                            $rule->where('guard_name', $get('guard_name') ?? $registry->getGuard());

                            if (static::isScopedToTenant() && ($tenant = alfakit()->getTenant()) !== null) {
                                $rule->where(PermissionModels::teamForeignKey(), $tenant->getKey());
                            }

                            return $rule;
                        }),
                    TextInput::make('guard_name')
                        ->label(__('alfakit-shield::shield.fields.guard_name'))
                        ->default($registry->getGuard())
                        ->required()
                        ->maxLength(255),
                    Toggle::make('select_all')
                        ->label(__('alfakit-shield::shield.fields.select_all'))
                        ->helperText(__('alfakit-shield::shield.fields.select_all_help'))
                        ->dehydrated(false)
                        ->afterStateHydrated(static function (Toggle $component, ?Model $record) use ($lists): void {
                            $component->state($record instanceof Role && static::roleHasAll($record, $lists));
                        })
                        ->afterStateUpdated(static function (mixed $state, Set $set) use ($lists): void {
                            foreach ($lists as $key => $options) {
                                $set(self::PERMISSIONS_KEY.'.'.$key, $state ? array_keys($options) : [], isAbsolute: true);
                            }
                        }),
                ]),
            Group::make()
                ->statePath(self::PERMISSIONS_KEY)
                ->schema([
                    Tabs::make(__('alfakit-shield::shield.tabs.label'))
                        ->contained()
                        ->tabs(static::getPermissionTabs($registry)),
                ]),
        ]);
    }

    /**
     * @return array<Tab>
     */
    protected static function getPermissionTabs(PermissionRegistry $registry): array
    {
        $plugin = $registry->getPlugin();
        $tabs = [];

        $resources = $registry->resources();

        if ($plugin->hasTab('resources') && $resources !== []) {
            $sections = [];

            foreach ($resources as $subject => $resource) {
                $sections[] = Section::make($resource['label'])
                    ->description($resource['model'])
                    ->compact()
                    ->collapsible()
                    // One column: a section's default two-column layout put the checkbox
                    // list in half the card, so its labels ran into each other.
                    ->columns(1)
                    ->columnSpan($plugin->getSectionColumnSpan())
                    ->schema([
                        static::makeCheckboxList("resource_{$subject}", $resource['permissions'])
                            ->columns($plugin->getResourceCheckboxListColumns()),
                    ]);
            }

            $tabs[] = Tab::make(__('alfakit-shield::shield.tabs.resources'))
                ->key('resources')
                ->badge(count($resources))
                // Full width: a tab lays its components out in two columns, which squeezed the grid of
                // resource sections into half the tab and the checkbox labels into each other.
                ->schema([Grid::make($plugin->getGridColumns())->schema($sections)->columnSpanFull()]);
        }

        $pages = array_column($registry->pages(), 'label', 'permission');

        if ($plugin->hasTab('pages') && $pages !== []) {
            $tabs[] = Tab::make(__('alfakit-shield::shield.tabs.pages'))
                ->key('pages')
                ->badge(count($pages))
                ->schema([static::makeCheckboxList('pages', $pages)->columns($plugin->getCheckboxListColumns())->columnSpanFull()]);
        }

        $widgets = array_column($registry->widgets(), 'label', 'permission');

        if ($plugin->hasTab('widgets') && $widgets !== []) {
            $tabs[] = Tab::make(__('alfakit-shield::shield.tabs.widgets'))
                ->key('widgets')
                ->badge(count($widgets))
                ->schema([static::makeCheckboxList('widgets', $widgets)->columns($plugin->getCheckboxListColumns())->columnSpanFull()]);
        }

        $custom = $registry->customPermissionsWithStored();

        if ($plugin->hasTab('custom') && $custom !== []) {
            $tabs[] = Tab::make(__('alfakit-shield::shield.tabs.custom'))
                ->key('custom')
                ->badge(count($custom))
                ->schema([static::makeCheckboxList('custom', $custom)->columns($plugin->getCheckboxListColumns())->columnSpanFull()]);
        }

        return $tabs;
    }

    /**
     * @param  array<string, string>  $options  permission name => label
     */
    protected static function makeCheckboxList(string $name, array $options): CheckboxList
    {
        return CheckboxList::make($name)
            ->hiddenLabel()
            ->options($options)
            ->bulkToggleable()
            ->default([])
            ->afterStateHydrated(static function (CheckboxList $component, ?Model $record) use ($options): void {
                if (! $record instanceof Role) {
                    return;
                }

                $granted = $record->permissions->pluck('name')->map(static fn (mixed $name): string => (string) $name)->all();

                $component->state(array_values(array_intersect(array_keys($options), $granted)));
            });
    }

    /**
     * The options of every checkbox list of the form, by list name.
     *
     * @return array<string, array<string, string>>
     */
    public static function getPermissionOptions(?PermissionRegistry $registry = null): array
    {
        $registry ??= Shield::registry();
        $plugin = $registry->getPlugin();
        $lists = [];

        if ($plugin->hasTab('resources')) {
            foreach ($registry->resources() as $subject => $resource) {
                $lists["resource_{$subject}"] = $resource['permissions'];
            }
        }

        if ($plugin->hasTab('pages')) {
            $lists['pages'] = array_column($registry->pages(), 'label', 'permission');
        }

        if ($plugin->hasTab('widgets')) {
            $lists['widgets'] = array_column($registry->widgets(), 'label', 'permission');
        }

        if ($plugin->hasTab('custom')) {
            $lists['custom'] = $registry->customPermissionsWithStored();
        }

        return array_filter($lists, static fn (array $options): bool => $options !== []);
    }

    /**
     * Take the checked permissions out of the submitted form data. Only permissions the
     * form offers are kept.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function pullPermissions(array &$data): array
    {
        $submitted = $data[self::PERMISSIONS_KEY] ?? [];
        unset($data[self::PERMISSIONS_KEY], $data['select_all']);

        if (! is_array($submitted)) {
            return [];
        }

        $offered = [];

        foreach (static::getPermissionOptions() as $options) {
            $offered += $options;
        }

        $names = [];

        foreach ($submitted as $values) {
            foreach ((array) $values as $value) {
                if (is_string($value) && isset($offered[$value])) {
                    $names[] = $value;
                }
            }
        }

        return array_values(array_unique($names));
    }

    /**
     * Give the role exactly these permissions, creating missing ones for its guard.
     *
     * @param  list<string>  $permissions
     */
    public static function syncRolePermissions(Role $role, array $permissions): void
    {
        $guard = (string) $role->getAttribute('guard_name');

        PermissionModels::ensurePermissions($permissions, $guard);

        $model = PermissionModels::permission();

        $role->syncPermissions($model::query()->where('guard_name', $guard)->whereIn('name', $permissions)->get());
    }

    /**
     * @param  array<string, array<string, string>>  $lists
     */
    protected static function roleHasAll(Role $role, array $lists): bool
    {
        $all = [];

        foreach ($lists as $options) {
            array_push($all, ...array_keys($options));
        }

        if ($all === []) {
            return false;
        }

        $granted = $role->permissions->pluck('name')->map(static fn (mixed $name): string => (string) $name)->all();

        return array_diff($all, $granted) === [];
    }

    // ---------------------------------------------------------------- Table

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('alfakit-shield::shield.columns.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('guard_name')
                    ->label(__('alfakit-shield::shield.columns.guard_name'))
                    ->badge(),
                TextColumn::make('permissions_count')
                    ->label(__('alfakit-shield::shield.columns.permissions'))
                    ->counts('permissions')
                    ->badge(),
                TextColumn::make('updated_at')
                    ->label(__('alfakit-shield::shield.columns.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
