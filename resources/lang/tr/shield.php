<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'rol',
        'plural_label' => 'roller',
        'navigation_label' => 'Roller',
    ],

    'fields' => [
        'name' => 'Ad',
        'guard_name' => 'Guard',
        'select_all' => 'Tümünü seç',
        'select_all_help' => 'Bu role aşağıda listelenen tüm izinleri verin.',
    ],

    'tabs' => [
        'label' => 'İzinler',
        'resources' => 'Kaynaklar',
        'pages' => 'Sayfalar',
        'widgets' => 'Bileşenler',
        'custom' => 'Özel izinler',
    ],

    'columns' => [
        'name' => 'Ad',
        'guard_name' => 'Guard',
        'permissions' => 'İzinler',
        'updated_at' => 'Güncellenme tarihi',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'Tümünü görüntüle',
        'view' => 'Görüntüle',
        'create' => 'Oluştur',
        'update' => 'Güncelle',
        'delete' => 'Sil',
        'delete_any' => 'Herhangi birini sil',
        'restore' => 'Geri yükle',
        'restore_any' => 'Herhangi birini geri yükle',
        'force_delete' => 'Kalıcı olarak sil',
        'force_delete_any' => 'Herhangi birini kalıcı olarak sil',
        'replicate' => 'Çoğalt',
        'reorder' => 'Yeniden sırala',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
