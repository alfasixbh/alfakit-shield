<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'دور',
        'plural_label' => 'الأدوار',
        'navigation_label' => 'الأدوار',
    ],

    'fields' => [
        'name' => 'الاسم',
        'guard_name' => 'الحارس',
        'select_all' => 'تحديد الكل',
        'select_all_help' => 'منح هذا الدور جميع الصلاحيات المدرجة أدناه.',
    ],

    'tabs' => [
        'label' => 'الصلاحيات',
        'resources' => 'الموارد',
        'pages' => 'الصفحات',
        'widgets' => 'الأدوات',
        'custom' => 'صلاحيات مخصصة',
    ],

    'columns' => [
        'name' => 'الاسم',
        'guard_name' => 'الحارس',
        'permissions' => 'الصلاحيات',
        'updated_at' => 'تاريخ التحديث',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'عرض الكل',
        'view' => 'عرض',
        'create' => 'إنشاء',
        'update' => 'تحديث',
        'delete' => 'حذف',
        'delete_any' => 'حذف أي سجل',
        'restore' => 'استعادة',
        'restore_any' => 'استعادة أي سجل',
        'force_delete' => 'حذف نهائي',
        'force_delete_any' => 'حذف نهائي لأي سجل',
        'replicate' => 'تكرار',
        'reorder' => 'إعادة الترتيب',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
