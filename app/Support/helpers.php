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

if (! function_exists('amount_in_words')) {
    /**
     * "One Thousand Two Hundred Pesos and 50/100" for printed receipts and vouchers.
     * Returns null when the intl extension is unavailable.
     */
    function amount_in_words(float|int|string $amount): ?string
    {
        if (! class_exists(\NumberFormatter::class)) {
            return null;
        }

        $pesos = (int) floor((float) $amount);
        $centavos = (int) round(((float) $amount - $pesos) * 100);
        $words = ucwords((new \NumberFormatter('en', \NumberFormatter::SPELLOUT))->format($pesos));

        return $words.' Pesos'.($centavos ? ' and '.str_pad((string) $centavos, 2, '0', STR_PAD_LEFT).'/100' : ' Only');
    }
}
