<?php

namespace App\Tests\Catalog;

use App\Catalog\ProductCatalog;
use PHPUnit\Framework\TestCase;

final class ProductCatalogTest extends TestCase
{
    public function testOfficialCatalogContainsNineCompleteUniqueProducts(): void
    {
        $products = (new ProductCatalog())->all();

        self::assertCount(9, $products);
        self::assertCount(9, array_unique(array_column($products, 'sku')));
        self::assertCount(9, array_unique(array_column($products, 'slug')));

        foreach ($products as $product) {
            self::assertNotEmpty($product['name']);
            self::assertNotEmpty($product['category']['name']);
            self::assertNotEmpty($product['benefits']);
            self::assertNotEmpty($product['ingredients']);
            self::assertFileExists(dirname(__DIR__, 2).'/public/'.$product['imagePath']);
        }
    }
}
