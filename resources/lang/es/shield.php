<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'rol',
        'plural_label' => 'roles',
        'navigation_label' => 'Roles',
    ],

    'fields' => [
        'name' => 'Nombre',
        'guard_name' => 'Guard',
        'select_all' => 'Seleccionar todo',
        'select_all_help' => 'Conceder a este rol todos los permisos que se indican a continuación.',
    ],

    'tabs' => [
        'label' => 'Permisos',
        'resources' => 'Recursos',
        'pages' => 'Páginas',
        'widgets' => 'Widgets',
        'custom' => 'Permisos personalizados',
    ],

    'columns' => [
        'name' => 'Nombre',
        'guard_name' => 'Guard',
        'permissions' => 'Permisos',
        'updated_at' => 'Actualizado el',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Ver cualquiera',
        'view' => 'Ver',
        'create' => 'Crear',
        'update' => 'Actualizar',
        'delete' => 'Eliminar',
        'delete_any' => 'Eliminar cualquiera',
        'restore' => 'Restaurar',
        'restore_any' => 'Restaurar cualquiera',
        'force_delete' => 'Forzar eliminación',
        'force_delete_any' => 'Forzar eliminación de cualquiera',
        'replicate' => 'Duplicar',
        'reorder' => 'Reordenar',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
