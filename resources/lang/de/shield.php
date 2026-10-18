<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'Rolle',
        'plural_label' => 'Rollen',
        'navigation_label' => 'Rollen',
    ],

    'fields' => [
        'name' => 'Name',
        'guard_name' => 'Guard',
        'select_all' => 'Alle auswählen',
        'select_all_help' => 'Dieser Rolle alle unten aufgeführten Berechtigungen geben.',
    ],

    'tabs' => [
        'label' => 'Berechtigungen',
        'resources' => 'Ressourcen',
        'pages' => 'Seiten',
        'widgets' => 'Widgets',
        'custom' => 'Eigene Berechtigungen',
    ],

    'columns' => [
        'name' => 'Name',
        'guard_name' => 'Guard',
        'permissions' => 'Berechtigungen',
        'updated_at' => 'Aktualisiert am',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Alle anzeigen',
        'view' => 'Anzeigen',
        'create' => 'Erstellen',
        'update' => 'Bearbeiten',
        'delete' => 'Löschen',
        'delete_any' => 'Beliebige löschen',
        'restore' => 'Wiederherstellen',
        'restore_any' => 'Beliebige wiederherstellen',
        'force_delete' => 'Endgültig löschen',
        'force_delete_any' => 'Beliebige endgültig löschen',
        'replicate' => 'Duplizieren',
        'reorder' => 'Neu anordnen',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
