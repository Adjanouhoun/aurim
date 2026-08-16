<?php

namespace App\Tests\Functional;

use App\Entity\CustomerOrder;
use App\Entity\Inventory;

final class StorefrontFlowTest extends AurimWebTestCase
{
    public function testCustomerCanSelectCountryOrderAndTrackPickup(): void
    {
        $crawler = $this->client->request('GET', '/boutique');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Soin Éclat AURIM');
        self::assertSelectorTextContains('.catalog-card strong', 'Choisissez votre pays');

        $countryForm = $crawler->filter('form.market-selector')->form(['market' => 'SN']);
        $this->client->submit($countryForm);
        self::assertResponseRedirects();

        $crawler = $this->client->request('GET', '/produit/soin-eclat-test');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.pdp-price', '10 000 XOF');
        self::assertSelectorTextContains('.pdp-availability', '10 disponibles');

        $addForm = $crawler->filter('form.pdp-buy')->form(['quantity' => 2]);
        $this->client->submit($addForm);
        self::assertResponseRedirects('/panier');
        $crawler = $this->client->followRedirect();
        self::assertSelectorTextContains('.cart-heading', 'Mon panier');
        self::assertSelectorTextContains('.cart-summary', '20 000 XOF');

        $crawler = $this->client->request('GET', '/commande');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.checkout-heading', 'Sénégal');
        $checkoutForm = $crawler->filter('form.checkout-form')->form([
            'name' => 'Cliente Test AURIM',
            'email' => 'cliente@example.test',
            'phone' => '+221770000000',
            'shipping_rate' => (string) $this->pickupRate->getId(),
            'payment_method' => (string) $this->cashMethod->getId(),
            'address' => 'Quartier du Plateau, Dakar',
        ]);
        $this->client->submit($checkoutForm);
        self::assertResponseRedirects();
        self::assertEmailCount(3);
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.order-confirmation h1', 'Merci, Cliente Test AURIM');
        self::assertSelectorTextContains('.manual-payment', 'Espèces au retrait');

        $order = $this->entityManager->getRepository(CustomerOrder::class)->findOneBy(['email' => 'cliente@example.test']);
        self::assertInstanceOf(CustomerOrder::class, $order);
        self::assertSame('preparing', $order->getStatus());
        self::assertSame(20000, $order->getTotalMinor());
        $updatedInventory = $this->entityManager->getRepository(Inventory::class)->find($this->inventory->getId());
        self::assertInstanceOf(Inventory::class, $updatedInventory);
        self::assertSame(2, $updatedInventory->getQuantityReserved());

        $crawler = $this->client->request('GET', '/suivi-commande');
        $trackingForm = $crawler->filter('.tracking-search form')->form([
            'reference' => $order->getReference(),
            'email' => 'cliente@example.test',
        ]);
        $this->client->submit($trackingForm);
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.tracking-result-head', $order->getReference());
        self::assertSelectorTextContains('.tracking-result-head h2', 'En préparation');
    }

    public function testTrackingRequiresMatchingReferenceAndEmail(): void
    {
        $crawler = $this->client->request('GET', '/suivi-commande');
        $form = $crawler->filter('.tracking-search form')->form([
            'reference' => 'AUR-TEST-INCONNUE',
            'email' => 'inconnue@example.test',
        ]);
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.tracking-error', 'Aucune commande ne correspond');
        self::assertSelectorNotExists('.tracking-result');
    }
}
