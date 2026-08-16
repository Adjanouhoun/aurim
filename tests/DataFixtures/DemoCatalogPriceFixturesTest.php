<?php

namespace App\Tests\DataFixtures;

use App\DataFixtures\DemoCatalogPriceFixtures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DemoCatalogPriceFixturesTest extends TestCase
{
    #[DataProvider('formattedPrices')]
    public function testDemoPricesUseTheExpectedCurrencyScale(int $amountMinor, string $currency, string $expected): void
    {
        $formatter = new \App\Store\PriceFormatter();

        self::assertSame($expected, $formatter->format($amountMinor, $currency));
    }

    /** @return iterable<string, array{int, string, string}> */
    public static function formattedPrices(): iterable
    {
        yield 'Mauritanie' => [65000, 'MRU', '650,00 MRU'];
        yield 'Sénégal' => [11900, 'XOF', '11 900 XOF'];
        yield 'Mali' => [11500, 'XOF', '11 500 XOF'];
        yield 'Guinée' => [175000, 'GNF', '175 000 GNF'];
    }
}
