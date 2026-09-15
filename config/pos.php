<?php

return [
    'currency' => env('POS_CURRENCY', 'PHP'),
    'currency_symbol' => env('POS_CURRENCY_SYMBOL', '₱'),
    'tax_rate' => (float) env('POS_TAX_RATE', 0.12),
    'tax_inclusive' => (bool) env('POS_TAX_INCLUSIVE', false),
    'low_stock_default' => (int) env('POS_LOW_STOCK_DEFAULT', 5),
    'units' => ['pcs', 'set', 'pair', 'liter', 'box', 'roll', 'meter', 'gallon', 'kit'],
];
