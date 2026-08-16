<?php

namespace App\Store;

final class PriceFormatter
{
    public function format(int $amountMinor, string $currencyCode): string
    {
        $decimals = in_array($currencyCode, ['XOF', 'GNF'], true) ? 0 : 2;
        $divisor = 10 ** $decimals;

        return number_format($amountMinor / $divisor, $decimals, ',', ' ').' '.$currencyCode;
    }
}
