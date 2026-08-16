<?php

namespace App\Twig;

use App\Store\PriceFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class PriceExtension extends AbstractExtension
{
    public function __construct(private readonly PriceFormatter $priceFormatter) {}

    public function getFilters(): array
    {
        return [new TwigFilter('price', $this->format(...))];
    }

    public function format(int $amountMinor, string $currencyCode): string
    {
        return $this->priceFormatter->format($amountMinor, $currencyCode);
    }
}
