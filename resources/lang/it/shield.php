<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'ruolo',
        'plural_label' => 'ruoli',
        'navigation_label' => 'Ruoli',
    ],

    'fields' => [
        'name' => 'Nome',
        'guard_name' => 'Guard',
        'select_all' => 'Seleziona tutto',
        'select_all_help' => 'Assegna a questo ruolo tutti i permessi elencati di seguito.',
    ],

    'tabs' => [
        'label' => 'Permessi',
        'resources' => 'Risorse',
        'pages' => 'Pagine',
        'widgets' => 'Widget',
        'custom' => 'Permessi personalizzati',
    ],

    'columns' => [
        'name' => 'Nome',
        'guard_name' => 'Guard',
        'permissions' => 'Permessi',
        'updated_at' => 'Aggiornato il',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Visualizza qualsiasi',
        'view' => 'Visualizza',
        'create' => 'Crea',
        'update' => 'Aggiorna',
        'delete' => 'Elimina',
        'delete_any' => 'Elimina qualsiasi',
        'restore' => 'Ripristina',
        'restore_any' => 'Ripristina qualsiasi',
        'force_delete' => 'Elimina definitivamente',
        'force_delete_any' => 'Elimina definitivamente qualsiasi',
        'replicate' => 'Duplica',
        'reorder' => 'Riordina',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
