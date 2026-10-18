<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'rôle',
        'plural_label' => 'rôles',
        'navigation_label' => 'Rôles',
    ],

    'fields' => [
        'name' => 'Nom',
        'guard_name' => 'Guard',
        'select_all' => 'Tout sélectionner',
        'select_all_help' => 'Accorder à ce rôle toutes les permissions listées ci-dessous.',
    ],

    'tabs' => [
        'label' => 'Permissions',
        'resources' => 'Ressources',
        'pages' => 'Pages',
        'widgets' => 'Widgets',
        'custom' => 'Permissions personnalisées',
    ],

    'columns' => [
        'name' => 'Nom',
        'guard_name' => 'Guard',
        'permissions' => 'Permissions',
        'updated_at' => 'Mis à jour le',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Afficher tout',
        'view' => 'Afficher',
        'create' => 'Créer',
        'update' => 'Modifier',
        'delete' => 'Supprimer',
        'delete_any' => 'Supprimer tout',
        'restore' => 'Restaurer',
        'restore_any' => 'Restaurer tout',
        'force_delete' => 'Forcer la suppression',
        'force_delete_any' => 'Forcer la suppression de tout',
        'replicate' => 'Dupliquer',
        'reorder' => 'Réorganiser',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
