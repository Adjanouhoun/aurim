<?php

namespace App\Tests\Functional;

use App\Entity\Category;
use App\Entity\Inventory;
use App\Entity\Market;
use App\Entity\MarketPrice;
use App\Entity\PaymentMethod;
use App\Entity\Product;
use App\Entity\ShippingRate;
use App\Entity\User;
use App\Entity\Warehouse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class AurimWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $entityManager;
    protected Market $senegal;
    protected Product $product;
    protected Inventory $inventory;
    protected ShippingRate $pickupRate;
    protected PaymentMethod $cashMethod;
    protected User $localAdmin;
    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        (new SchemaTool($this->entityManager))->createSchema($metadata);
        $this->seedStore();
    }

    private function seedStore(): void
    {
        $this->senegal = (new Market())
            ->setCountryCode('SN')
            ->setName('Sénégal')
            ->setCurrencyCode('XOF')
            ->setActive(true);
        $warehouse = (new Warehouse())
            ->setCode('SN-TEST')
            ->setName('Stock local Sénégal')
            ->setMarket($this->senegal)
            ->setCentral(false)
            ->setActive(true);
        $this->senegal->setWarehouse($warehouse);

        $category = (new Category())
            ->setName('Soin test')
            ->setSlug('soin-test')
            ->setPosition(1)
            ->setActive(true);
        $this->product = (new Product())
            ->setSlug('soin-eclat-test')
            ->setSku('AUR-TEST-01')
            ->setName('Soin Éclat AURIM')
            ->setNameEn('AURIM Glow Care')
            ->setNameAr('عناية أوريم للإشراقة')
            ->setType('Soin illuminateur')
            ->setTypeEn('Radiance treatment')
            ->setTypeAr('عناية للإشراقة')
            ->setCategory($category)
            ->setSize('100 ml')
            ->setShortDescription('Un soin de test pour vérifier le parcours client.')
            ->setShortDescriptionEn('A test treatment for the complete customer journey.')
            ->setShortDescriptionAr('منتج تجريبي للتحقق من رحلة العميل كاملة.')
            ->setDescription('Une description complète du soin AURIM utilisé par les tests fonctionnels.')
            ->setBenefits(['Illumine', 'Hydrate'])
            ->setIngredients([['name' => 'Vitamine C', 'text' => 'Soutient l’éclat de la peau.']])
            ->setUsageInstructions('Appliquer une fois par jour.')
            ->setImagePath('images/products/aurim-body-care-duo.jpg')
            ->setImagePosition('center')
            ->setActive(true);

        $price = (new MarketPrice())
            ->setProduct($this->product)
            ->setMarket($this->senegal)
            ->setAmountMinor(10000)
            ->setPublished(true);
        $this->inventory = (new Inventory())
            ->setProduct($this->product)
            ->setWarehouse($warehouse)
            ->setQuantityOnHand(10)
            ->setQuantityReserved(0)
            ->setLowStockThreshold(2);
        $this->pickupRate = (new ShippingRate())
            ->setMarket($this->senegal)
            ->setFulfillmentType('pickup')
            ->setLabel('Dépôt AURIM Dakar')
            ->setCity('Dakar')
            ->setAddressLine('12 avenue AURIM, Dakar')
            ->setAmountMinor(0)
            ->setMinimumDays(1)
            ->setMaximumDays(2)
            ->setActive(true);
        $this->cashMethod = (new PaymentMethod())
            ->setMarket($this->senegal)
            ->setCode('cash-test-sn')
            ->setName('Espèces au retrait')
            ->setType('cash')
            ->setFulfillmentScope('pickup')
            ->setInstructions('Paiement lors du retrait.')
            ->setActive(true);

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->localAdmin = (new User())
            ->setEmail('responsable.sn@example.test')
            ->setRoles(['ROLE_ADMIN'])
            ->setMarket($this->senegal);
        $this->localAdmin->setPassword($hasher->hashPassword($this->localAdmin, 'Aurim-Test-2026!'));
        $this->superAdmin = (new User())
            ->setEmail('direction@example.test')
            ->setRoles(['ROLE_SUPER_ADMIN'])
            ->setMarket(null);
        $this->superAdmin->setPassword($hasher->hashPassword($this->superAdmin, 'Aurim-Test-2026!'));

        foreach ([$this->senegal, $warehouse, $category, $this->product, $price, $this->inventory, $this->pickupRate, $this->cashMethod, $this->localAdmin, $this->superAdmin] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
    }
}
