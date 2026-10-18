<?php

declare(strict_types=1);

return [

    'resource' => [
        'label' => 'نقش',
        'plural_label' => 'نقش‌ها',
        'navigation_label' => 'نقش‌ها',
    ],

    'fields' => [
        'name' => 'نام',
        'guard_name' => 'گارد',
        'select_all' => 'انتخاب همه',
        'select_all_help' => 'همهٔ مجوزهای فهرست‌شده در زیر را به این نقش بدهید.',
    ],

    'tabs' => [
        'label' => 'مجوزها',
        'resources' => 'منابع',
        'pages' => 'صفحه‌ها',
        'widgets' => 'ویجت‌ها',
        'custom' => 'مجوزهای سفارشی',
    ],

    'columns' => [
        'name' => 'نام',
        'guard_name' => 'گارد',
        'permissions' => 'مجوزها',
        'updated_at' => 'تاریخ به‌روزرسانی',
    ],

    // Permission labels used with `localizePermissionLabels()`.
    'prefixes' => [
        'view_any' => 'مشاهدهٔ همه',
        'view' => 'مشاهده',
        'create' => 'ایجاد',
        'update' => 'به‌روزرسانی',
        'delete' => 'حذف',
        'delete_any' => 'حذف هر مورد',
        'restore' => 'بازیابی',
        'restore_any' => 'بازیابی هر مورد',
        'force_delete' => 'حذف دائمی',
        'force_delete_any' => 'حذف دائمی هر مورد',
        'replicate' => 'تکثیر',
        'reorder' => 'تغییر ترتیب',
    ],

    // Labels of custom permissions with `localizePermissionLabels()`: 'export_orders' => 'Export orders'.
    'custom' => [],

];
