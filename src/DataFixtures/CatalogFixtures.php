<?php

namespace App\DataFixtures;

use App\Catalog\ProductCatalog;
use App\Catalog\ProductTranslations;
use App\Entity\Category;
use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\Product;
use App\Entity\Warehouse;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

final class CatalogFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private readonly ProductCatalog $catalog,
        private readonly ProductTranslations $translations,
    ) {}

    public static function getGroups(): array
    {
        return ['catalog'];
    }

    public function load(ObjectManager $manager): void
    {
        $marketDefinitions = [
            ['MR', 'Mauritanie', 'MRU', true],
            ['SN', 'Sénégal', 'XOF', true],
            ['ML', 'Mali', 'XOF', true],
            ['GN', 'Guinée', 'GNF', true],
            ['US', 'États-Unis (stock central)', 'USD', false],
        ];
        $warehouseDefinitions = [
            ['US-CENTRAL', 'Stock central États-Unis', 'US', true],
            ['MR-LOCAL', 'Stock local Mauritanie', 'MR', false],
            ['SN-LOCAL', 'Stock local Sénégal', 'SN', false],
            ['ML-LOCAL', 'Stock local Mali', 'ML', false],
            ['GN-LOCAL', 'Stock local Guinée', 'GN', false],
        ];

        $markets = [];
        $salesMarkets = [];
        foreach ($marketDefinitions as [$countryCode, $name, $currencyCode, $active]) {
            $market = $manager->getRepository(Market::class)->findOneBy(['countryCode' => $countryCode]);
            if (!$market instanceof Market) {
                $market = (new Market())->setCountryCode($countryCode)->setActive($active);
            }
            $market->setName($name)->setCurrencyCode($currencyCode);
            $markets[$countryCode] = $market;
            if ('US' !== $countryCode) {
                $salesMarkets[] = $market;
            }
            $manager->persist($market);
        }

        $warehouses = [];
        foreach ($warehouseDefinitions as [$code, $name, $countryCode, $central]) {
            $warehouse = $manager->getRepository(Warehouse::class)->findOneBy(['code' => $code]);
            if (!$warehouse instanceof Warehouse) {
                $warehouse = (new Warehouse())->setCode($code)->setActive(true);
            }
            $warehouse->setName($name)->setMarket($markets[$countryCode])->setCentral($central);
            $warehouses[] = $warehouse;
            $manager->persist($warehouse);
        }

        $categories = [];
        foreach ($this->catalog->all() as $data) {
            $categoryData = $data['category'];
            $translation = $this->translations->for($data['slug']);
            if (!isset($categories[$categoryData['slug']])) {
                $category = $manager->getRepository(Category::class)->findOneBy(['slug' => $categoryData['slug']]);
                if (!$category instanceof Category) {
                    $category = (new Category())->setSlug($categoryData['slug'])->setActive(true);
                }
                $category
                    ->setName($categoryData['name'])
                    ->setNameEn($translation['categoryEn'] ?? null)
                    ->setNameAr($translation['categoryAr'] ?? null)
                    ->setPosition($categoryData['position']);
                $manager->persist($category);
                $categories[$categoryData['slug']] = $category;
            }
            $category = $categories[$categoryData['slug']];

            $product = $manager->getRepository(Product::class)->findOneBy(['sku' => $data['sku']])
                ?? $manager->getRepository(Product::class)->findOneBy(['slug' => $data['slug']]);
            if (!$product instanceof Product) {
                $product = (new Product())->setActive(true);
            }
            $product
                ->setSku($data['sku'])
                ->setSlug($data['slug'])
                ->setName($data['name'])
                ->setNameEn($translation['nameEn'] ?? null)
                ->setNameAr($translation['nameAr'] ?? null)
                ->setCategory($category)
                ->setType($data['type'])
                ->setTypeEn($translation['typeEn'] ?? null)
                ->setTypeAr($translation['typeAr'] ?? null)
                ->setSize($data['size'])
                ->setShortDescription($data['shortDescription'])
                ->setShortDescriptionEn($translation['shortDescriptionEn'] ?? null)
                ->setShortDescriptionAr($translation['shortDescriptionAr'] ?? null)
                ->setDescription($data['description'])
                ->setDescriptionEn($translation['descriptionEn'] ?? null)
                ->setDescriptionAr($translation['descriptionAr'] ?? null)
                ->setBenefits($data['benefits'])
                ->setBenefitsEn($translation['benefitsEn'] ?? null)
                ->setBenefitsAr($translation['benefitsAr'] ?? null)
                ->setIngredients($data['ingredients'])
                ->setIngredientsEn($translation['ingredientsEn'] ?? null)
                ->setIngredientsAr($translation['ingredientsAr'] ?? null)
                ->setUsageInstructions($data['usage'])
                ->setUsageInstructionsEn($translation['usageEn'] ?? null)
                ->setUsageInstructionsAr($translation['usageAr'] ?? null)
                ->setImagePath($data['imagePath'])
                ->setImagePosition($data['imagePosition']);
            $manager->persist($product);

            foreach ($warehouses as $warehouse) {
                $inventory = $manager->getRepository(Inventory::class)->findOneBy([
                    'product' => $product,
                    'warehouse' => $warehouse,
                ]);
                if (!$inventory instanceof Inventory) {
                    $manager->persist((new Inventory())->setProduct($product)->setWarehouse($warehouse));
                }
            }

            foreach ($salesMarkets as $market) {
                $price = $manager->getRepository(MarketPrice::class)->findOneBy([
                    'product' => $product,
                    'market' => $market,
                ]);
                if (!$price instanceof MarketPrice) {
                    $manager->persist(
                        (new MarketPrice())
                            ->setProduct($product)
                            ->setMarket($market)
                            ->setPublished(false),
                    );
                }
            }
        }

        $manager->flush();
    }
}
