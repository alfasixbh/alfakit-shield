<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'rol',
        'plural_label' => 'rollen',
        'navigation_label' => 'Rollen',
    ],

    'fields' => [
        'name' => 'Naam',
        'guard_name' => 'Guard',
        'select_all' => 'Alles selecteren',
        'select_all_help' => 'Geef deze rol alle hieronder vermelde rechten.',
    ],

    'tabs' => [
        'label' => 'Rechten',
        'resources' => 'Resources',
        'pages' => 'Pagina’s',
        'widgets' => 'Widgets',
        'custom' => 'Aangepaste rechten',
    ],

    'columns' => [
        'name' => 'Naam',
        'guard_name' => 'Guard',
        'permissions' => 'Rechten',
        'updated_at' => 'Bijgewerkt op',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Alles bekijken',
        'view' => 'Bekijken',
        'create' => 'Aanmaken',
        'update' => 'Bijwerken',
        'delete' => 'Verwijderen',
        'delete_any' => 'Alles verwijderen',
        'restore' => 'Herstellen',
        'restore_any' => 'Alles herstellen',
        'force_delete' => 'Definitief verwijderen',
        'force_delete_any' => 'Alles definitief verwijderen',
        'replicate' => 'Dupliceren',
        'reorder' => 'Herordenen',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
