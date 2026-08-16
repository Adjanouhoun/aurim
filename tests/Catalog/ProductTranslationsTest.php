<?php

namespace App\Tests\Catalog;

use App\Catalog\ProductCatalog;
use App\Catalog\ProductTranslations;
use PHPUnit\Framework\TestCase;

final class ProductTranslationsTest extends TestCase
{
    public function testEveryCatalogProductHasCompleteEnglishAndArabicContent(): void
    {
        $translations = new ProductTranslations();

        foreach ((new ProductCatalog())->all() as $product) {
            $localized = $translations->for($product['slug']);
            foreach (['name', 'type', 'shortDescription', 'description', 'benefits', 'ingredients', 'usage'] as $field) {
                self::assertNotEmpty($localized[$field.'En'] ?? null, $product['slug'].' is missing '.$field.'En');
                self::assertNotEmpty($localized[$field.'Ar'] ?? null, $product['slug'].' is missing '.$field.'Ar');
            }
        }
    }
}
