<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'role',
        'plural_label' => 'roles',
        'navigation_label' => 'Roles',
    ],

    'fields' => [
        'name' => 'Name',
        'guard_name' => 'Guard',
        'select_all' => 'Select all',
        'select_all_help' => 'Give this role every permission listed below.',
    ],

    'tabs' => [
        'label' => 'Permissions',
        'resources' => 'Resources',
        'pages' => 'Pages',
        'widgets' => 'Widgets',
        'custom' => 'Custom permissions',
    ],

    'columns' => [
        'name' => 'Name',
        'guard_name' => 'Guard',
        'permissions' => 'Permissions',
        'updated_at' => 'Updated at',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'View any',
        'view' => 'View',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
        'delete_any' => 'Delete any',
        'restore' => 'Restore',
        'restore_any' => 'Restore any',
        'force_delete' => 'Force delete',
        'force_delete_any' => 'Force delete any',
        'replicate' => 'Replicate',
        'reorder' => 'Reorder',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
