<?php

/*
|--------------------------------------------------------------------------
| Role Permissions
|--------------------------------------------------------------------------
|
| Maps each role to the modules it may access. Admins are granted every
| permission automatically and do not need to be listed here. Each
| permission is checked as a Gate ability (e.g. @can('inventory') or the
| 'can:inventory' route middleware).
|
*/

return [
    'permissions' => [
        'dashboard' => 'Dashboard & analytics',
        'pos' => 'Point of Sale',
        'sales' => 'Sales history & collecting payments',
        'sales.void' => 'Void sales',
        'sales.return' => 'Return items & issue credit memos',
        'inventory' => 'Products, stock in, barcodes, categories, brands, suppliers, import',
        'transfers' => 'Stock transfers between branches & warehouses',
        'customers' => 'Customers',
        'reports' => 'Sales, inventory and product reports',
        'accounting' => 'Profit & loss, expenses, receivables, payables',
        'hr' => 'HR: employees, attendance, cash advances & payroll',
        'settings' => 'Store settings',
        'backup' => 'Backup & restore',
        'branches' => 'Branch & warehouse management',
        'users' => 'User management',
    ],

    'roles' => [
        'manager' => [
            'dashboard',
            'pos',
            'sales',
            'sales.void',
            'sales.return',
            'inventory',
            'transfers',
            'customers',
            'reports',
            'accounting',
        ],

        'cashier' => [
            'pos',
            'sales',
            'customers',
        ],
    ],
];
