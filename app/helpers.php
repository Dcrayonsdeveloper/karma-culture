<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

if (!function_exists('currency_symbol')) {
    function currency_symbol(): string
    {
        return currency_config('symbol');
    }
}

if (!function_exists('currency_position')) {
    function currency_position(): string
    {
        return currency_config('position');
    }
}

if (!function_exists('format_price')) {
    function format_price(float|int|string|null $amount, int $decimals = 2): string
    {
        if ($amount === null) {
            $amount = 0;
        }

        $symbol = currency_symbol();
        $position = currency_position();
        $formatted = number_format((float) $amount, $decimals);

        return $position === 'after'
            ? $formatted . $symbol
            : $symbol . $formatted;
    }
}

if (!function_exists('currency_config')) {
    function currency_config(?string $key = null): mixed
    {
        $config = Cache::remember('currency_config', 3600, function () {
            return [
                'symbol' => Setting::get('currency_symbol', '₹'),
                'position' => Setting::get('currency_position', 'before'),
                'code' => Setting::get('currency', 'INR'),
            ];
        });

        return $key ? ($config[$key] ?? null) : $config;
    }
}
