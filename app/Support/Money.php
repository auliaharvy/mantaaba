<?php

namespace App\Support;

/** Uang dalam sen (integer) untuk perbandingan & penjumlahan tanpa galat float (PRD 6.1). */
class Money
{
    public static function cents($value): int
    {
        $value = trim((string) $value);
        if ($value === '') {
            return 0;
        }
        $negative = str_starts_with($value, '-');
        [$whole, $fraction] = array_pad(explode('.', ltrim($value, '-'), 2), 2, '0');
        $cents = (int) $whole * 100 + (int) str_pad(substr($fraction, 0, 2), 2, '0');

        return $negative ? -$cents : $cents;
    }

    public static function decimal(int $cents): string
    {
        return ($cents < 0 ? '-' : '') . intdiv(abs($cents), 100) . '.' . str_pad((string) (abs($cents) % 100), 2, '0', STR_PAD_LEFT);
    }

    public static function format($value): string
    {
        return 'Rp ' . number_format((float) $value, 0, ',', '.');
    }
}
