<?php

namespace App\Tests\Functional;

use App\Entity\Market;
use App\Entity\User;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OrderAdminNotificationTest extends AurimWebTestCase
{
    public function testOrderNotifiesLocalAndSuperAdminsButNotAnotherMarketAdmin(): void
    {
        $mali = (new Market())
            ->setCountryCode('ML')
            ->setName('Mali')
            ->setCurrencyCode('XOF')
            ->setActive(true);
        $maliAdmin = (new User())
            ->setEmail('responsable.ml@example.test')
            ->setRoles(['ROLE_ADMIN'])
            ->setMarket($mali);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $maliAdmin->setPassword($hasher->hashPassword($maliAdmin, 'Aurim-Test-2026!'));
        $this->entityManager->persist($mali);
        $this->entityManager->persist($maliAdmin);
        $this->entityManager->flush();

        $this->placeSenegalOrder();

        self::assertEmailCount(3);
        $adminEmails = $this->findAdminEmails();
        $recipients = [];
        foreach ($adminEmails as $adminEmail) {
            foreach ($adminEmail->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        }
        $recipients = array_values(array_unique($recipients));
        self::assertCount(2, $recipients);
        self::assertContains('responsable.sn@example.test', $recipients);
        self::assertContains('direction@example.test', $recipients);
        self::assertNotContains('responsable.ml@example.test', $recipients);
    }

    private function placeSenegalOrder(): void
    {
        $crawler = $this->client->request('GET', '/boutique');
        $this->client->submit($crawler->filter('form.market-selector')->first()->form(['market' => 'SN']));
        $crawler = $this->client->request('GET', '/produit/soin-eclat-test');
        $this->client->submit($crawler->filter('form.pdp-buy')->form(['quantity' => 1]));

        $crawler = $this->client->request('GET', '/commande');
        $this->client->submit($crawler->filter('form.checkout-form')->form([
            'name' => 'Cliente Notification',
            'email' => 'notification@example.test',
            'phone' => '+221770000003',
            'shipping_rate' => (string) $this->pickupRate->getId(),
            'payment_method' => (string) $this->cashMethod->getId(),
            'address' => 'Quartier du Plateau, Dakar',
        ]));
        self::assertResponseRedirects();
    }

    /** @return list<Email> */
    private function findAdminEmails(): array
    {
        $emails = [];
        foreach (self::getMailerMessages() as $message) {
            if ($message instanceof Email && str_starts_with((string) $message->getSubject(), 'Nouvelle commande')) {
                $emails[] = $message;
            }
        }

        return $emails;
    }
}
