<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'роль',
        'plural_label' => 'роли',
        'navigation_label' => 'Роли',
    ],

    'fields' => [
        'name' => 'Название',
        'guard_name' => 'Guard',
        'select_all' => 'Выбрать все',
        'select_all_help' => 'Предоставить этой роли все разрешения, перечисленные ниже.',
    ],

    'tabs' => [
        'label' => 'Разрешения',
        'resources' => 'Ресурсы',
        'pages' => 'Страницы',
        'widgets' => 'Виджеты',
        'custom' => 'Собственные разрешения',
    ],

    'columns' => [
        'name' => 'Название',
        'guard_name' => 'Guard',
        'permissions' => 'Разрешения',
        'updated_at' => 'Обновлено',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Просмотр всех',
        'view' => 'Просмотр',
        'create' => 'Создание',
        'update' => 'Изменение',
        'delete' => 'Удаление',
        'delete_any' => 'Удаление любых',
        'restore' => 'Восстановление',
        'restore_any' => 'Восстановление любых',
        'force_delete' => 'Удаление навсегда',
        'force_delete_any' => 'Удаление любых навсегда',
        'replicate' => 'Дублирование',
        'reorder' => 'Изменение порядка',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
