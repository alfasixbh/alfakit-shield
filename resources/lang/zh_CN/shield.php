<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => '角色',
        'plural_label' => '角色',
        'navigation_label' => '角色',
    ],

    'fields' => [
        'name' => '名称',
        'guard_name' => '守卫',
        'select_all' => '全选',
        'select_all_help' => '为此角色授予下方列出的所有权限。',
    ],

    'tabs' => [
        'label' => '权限',
        'resources' => '资源',
        'pages' => '页面',
        'widgets' => '小组件',
        'custom' => '自定义权限',
    ],

    'columns' => [
        'name' => '名称',
        'guard_name' => '守卫',
        'permissions' => '权限',
        'updated_at' => '更新时间',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => '查看全部',
        'view' => '查看',
        'create' => '创建',
        'update' => '更新',
        'delete' => '删除',
        'delete_any' => '删除任意',
        'restore' => '恢复',
        'restore_any' => '恢复任意',
        'force_delete' => '强制删除',
        'force_delete_any' => '强制删除任意',
        'replicate' => '复制',
        'reorder' => '排序',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
