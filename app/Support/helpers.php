<?php

use Illuminate\Support\Number;

if (! function_exists('peso')) {
    function peso(float|int|string|null $amount, bool $withSymbol = true): string
    {
        $value = (float) $amount;

        return $withSymbol
            ? Number::currency($value, 'PHP', 'en_PH')
            : number_format($value, 2);
    }
}

if (! function_exists('hrs')) {
    function hrs(float|int|string|null $hours, int $decimals = 2): string
    {
        return number_format((float) $hours, $decimals);
    }
}

if (! function_exists('mins')) {
    function mins(int|string|null $minutes): string
    {
        $m = (int) $minutes;

        return intdiv($m, 60).'h '.($m % 60).'m';
    }
}
