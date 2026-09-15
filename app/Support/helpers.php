<?php

use App\Models\Setting;

if (! function_exists('money')) {
    function money(float|int|string $amount): string
    {
        $symbol = Setting::get('currency_symbol', config('pos.currency_symbol'));

        return $symbol.number_format((float) $amount, 2);
    }
}

if (! function_exists('qty')) {
    function qty(float|int|string $quantity): string
    {
        $value = (float) $quantity;

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') ?: '0';
    }
}

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::get($key, $default);
    }
}
