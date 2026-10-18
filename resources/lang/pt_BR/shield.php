<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'função',
        'plural_label' => 'funções',
        'navigation_label' => 'Funções',
    ],

    'fields' => [
        'name' => 'Nome',
        'guard_name' => 'Guard',
        'select_all' => 'Selecionar tudo',
        'select_all_help' => 'Conceder a esta função todas as permissões listadas abaixo.',
    ],

    'tabs' => [
        'label' => 'Permissões',
        'resources' => 'Recursos',
        'pages' => 'Páginas',
        'widgets' => 'Widgets',
        'custom' => 'Permissões personalizadas',
    ],

    'columns' => [
        'name' => 'Nome',
        'guard_name' => 'Guard',
        'permissions' => 'Permissões',
        'updated_at' => 'Atualizado em',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Visualizar qualquer',
        'view' => 'Visualizar',
        'create' => 'Criar',
        'update' => 'Atualizar',
        'delete' => 'Excluir',
        'delete_any' => 'Excluir qualquer',
        'restore' => 'Restaurar',
        'restore_any' => 'Restaurar qualquer',
        'force_delete' => 'Forçar exclusão',
        'force_delete_any' => 'Forçar exclusão de qualquer',
        'replicate' => 'Duplicar',
        'reorder' => 'Reordenar',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
