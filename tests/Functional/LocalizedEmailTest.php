<?php

namespace App\Tests\Functional;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Entity\PaymentMethod;
use App\Notification\OrderMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Mime\Email;

final class LocalizedEmailTest extends AurimWebTestCase
{
    /** @return iterable<string, array{string, string, string, string, string, string}> */
    public static function localeProvider(): iterable
    {
        yield 'français' => ['fr', 'Commande AURIM', 'Merci Cliente', 'Paiement confirmé', 'Nous avons bien reçu', 'prête au retrait'];
        yield 'anglais' => ['en', 'AURIM order', 'Thank you Cliente', 'Payment confirmed', 'We have received', 'ready for pickup'];
        yield 'arabe' => ['ar', 'طلب أوريم', 'شكراً Cliente', 'تم تأكيد الدفع', 'استلمنا دفعة', 'جاهز للاستلام'];
    }

    #[DataProvider('localeProvider')]
    public function testCustomerEmailsUseTheOrderLocale(
        string $locale,
        string $orderSubject,
        string $orderBody,
        string $paymentSubject,
        string $paymentBody,
        string $statusSubject,
    ): void {
        $mobileMethod = (new PaymentMethod())
            ->setMarket($this->senegal)
            ->setCode('mobile-test-'.$locale)
            ->setName('Mobile Money test')
            ->setNameEn('Test Mobile Money')
            ->setNameAr('موبايل موني تجريبي')
            ->setType('mobile_money_manual')
            ->setFulfillmentScope('pickup')
            ->setInstructions('Instructions de paiement.')
            ->setInstructionsEn('Payment instructions.')
            ->setInstructionsAr('تعليمات الدفع.')
            ->setRecipientAccount('+221770000001')
            ->setAccountHolder('AURIM Test')
            ->setActive(true);
        $this->entityManager->persist($mobileMethod);
        $this->entityManager->flush();

        $this->client->request('GET', '/langue/'.$locale.'?returnTo=/boutique');
        $crawler = $this->client->followRedirect();
        $this->client->submit($crawler->filter('form.market-selector')->first()->form(['market' => 'SN']));

        $crawler = $this->client->request('GET', '/produit/soin-eclat-test');
        $this->client->submit($crawler->filter('form.pdp-buy')->form(['quantity' => 1]));

        $crawler = $this->client->request('GET', '/commande');
        $this->client->submit($crawler->filter('form.checkout-form')->form([
            'name' => 'Cliente AURIM',
            'email' => $locale.'@example.test',
            'phone' => '+221770000002',
            'shipping_rate' => (string) $this->pickupRate->getId(),
            'payment_method' => (string) $mobileMethod->getId(),
            'address' => 'Quartier du Plateau, Dakar',
        ]));

        self::assertResponseRedirects();
        self::assertEmailCount(2);
        $confirmationEmail = $this->findEmailBySubject($orderSubject);
        self::assertNotNull($confirmationEmail);
        self::assertEmailSubjectContains($confirmationEmail, $orderSubject);
        self::assertEmailHtmlBodyContains($confirmationEmail, $orderBody);

        $order = $this->entityManager->getRepository(CustomerOrder::class)->findOneBy(['email' => $locale.'@example.test']);
        self::assertInstanceOf(CustomerOrder::class, $order);
        self::assertSame($locale, $order->getLocale());
        $payment = $this->entityManager->getRepository(Payment::class)->findOneBy(['customerOrder' => $order]);
        self::assertInstanceOf(Payment::class, $payment);

        $mailer = static::getContainer()->get(OrderMailer::class);
        $mailer->sendPaymentReceived($payment);
        self::assertEmailCount(3);
        $paymentEmail = $this->findEmailBySubject($paymentSubject);
        self::assertNotNull($paymentEmail);
        self::assertEmailSubjectContains($paymentEmail, $paymentSubject);
        self::assertEmailHtmlBodyContains($paymentEmail, $paymentBody);

        $mailer->sendStatusChanged($order, 'shipped');
        self::assertEmailCount(4);
        $statusEmail = $this->findEmailBySubject($statusSubject);
        self::assertNotNull($statusEmail);
        self::assertEmailSubjectContains($statusEmail, $statusSubject);
    }

    private function findEmailBySubject(string $subject): ?Email
    {
        foreach (self::getMailerMessages() as $message) {
            if ($message instanceof Email && str_contains((string) $message->getSubject(), $subject)) {
                return $message;
            }
        }

        return null;
    }
}
