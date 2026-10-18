<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'ロール',
        'plural_label' => 'ロール',
        'navigation_label' => 'ロール',
    ],

    'fields' => [
        'name' => '名前',
        'guard_name' => 'ガード',
        'select_all' => 'すべて選択',
        'select_all_help' => '以下のすべての権限をこのロールに付与します。',
    ],

    'tabs' => [
        'label' => '権限',
        'resources' => 'リソース',
        'pages' => 'ページ',
        'widgets' => 'ウィジェット',
        'custom' => 'カスタム権限',
    ],

    'columns' => [
        'name' => '名前',
        'guard_name' => 'ガード',
        'permissions' => '権限',
        'updated_at' => '更新日時',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'すべて表示',
        'view' => '表示',
        'create' => '作成',
        'update' => '更新',
        'delete' => '削除',
        'delete_any' => 'すべて削除',
        'restore' => '復元',
        'restore_any' => 'すべて復元',
        'force_delete' => '完全に削除',
        'force_delete_any' => 'すべて完全に削除',
        'replicate' => '複製',
        'reorder' => '並べ替え',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
