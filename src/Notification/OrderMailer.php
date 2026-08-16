<?php

namespace App\Notification;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $senderAddress,
        private readonly string $adminAddress,
    ) {}

    public function sendOrderCreated(CustomerOrder $order, Payment $payment): void
    {
        $context = [
            'order' => $order,
            'payment' => $payment,
            'confirmationUrl' => $this->urlGenerator->generate(
                'app_order_confirmation',
                ['reference' => $order->getReference()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject('Commande AURIM '.$order->getReference())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->context($context));

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to($this->adminAddress)
            ->replyTo($order->getEmail())
            ->subject('Nouvelle commande '.$order->getReference())
            ->htmlTemplate('emails/admin_new_order.html.twig')
            ->context($context));
    }

    public function sendPaymentReceived(Payment $payment): void
    {
        $order = $payment->getCustomerOrder();
        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject('Paiement confirmé — '.$order->getReference())
            ->htmlTemplate('emails/payment_received.html.twig')
            ->context(['order' => $order, 'payment' => $payment]));
    }

    public function sendStatusChanged(CustomerOrder $order, string $status): void
    {
        $content = match ($status) {
            'preparing' => [
                'subject' => 'Votre commande AURIM est en préparation',
                'title' => 'Votre commande est en préparation',
                'message' => 'Notre équipe prépare soigneusement votre commande. Nous vous informerons dès qu’elle sera prête à être remise ou expédiée.',
            ],
            'shipped' => 'pickup' === $order->getFulfillmentType() ? [
                'subject' => 'Votre commande AURIM est prête au retrait',
                'title' => 'Votre commande est prête',
                'message' => sprintf('Votre commande vous attend au dépôt %s. Présentez votre référence lors du retrait.', $order->getFulfillmentLabel()),
            ] : [
                'subject' => 'Votre commande AURIM est en livraison',
                'title' => 'Votre commande est en route',
                'message' => 'Votre commande a quitté notre dépôt et sera livrée à l’adresse indiquée.',
            ],
            'delivered' => [
                'subject' => 'Votre commande AURIM a été remise',
                'title' => 'Votre commande a été remise',
                'message' => 'Votre commande est maintenant terminée. Merci d’avoir choisi AURIM pour votre rituel beauté.',
            ],
            'cancelled' => [
                'subject' => 'Votre commande AURIM a été annulée',
                'title' => 'Commande annulée',
                'message' => 'Votre commande a été annulée. Si vous avez déjà effectué un paiement, contactez AURIM en indiquant votre référence.',
            ],
            'payment_failed' => [
                'subject' => 'Le paiement de votre commande AURIM a échoué',
                'title' => 'Paiement non validé',
                'message' => 'Nous n’avons pas pu valider votre paiement Mobile Money. La commande a été annulée et le stock réservé a été libéré.',
            ],
            'delivery_failed' => [
                'subject' => 'La livraison de votre commande AURIM a échoué',
                'title' => 'Livraison non aboutie',
                'message' => 'La livraison n’a pas pu être effectuée. Notre équipe vous contactera pour organiser une nouvelle tentative ou une annulation.',
            ],
            'refunded' => [
                'subject' => 'Votre commande AURIM a été remboursée',
                'title' => 'Remboursement enregistré',
                'message' => 'Le remboursement de votre commande a été enregistré. Le délai de réception dépend du moyen de paiement utilisé.',
            ],
            default => null,
        };
        if (null === $content) {
            return;
        }

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject($content['subject'].' — '.$order->getReference())
            ->htmlTemplate('emails/order_status_changed.html.twig')
            ->context(['order' => $order, 'title' => $content['title'], 'message' => $content['message']]));
    }

    private function send(TemplatedEmail $email): void
    {
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Échec de l’envoi d’un e-mail de commande AURIM.', [
                'exception' => $exception,
                'subject' => $email->getSubject(),
            ]);
        }
    }
}
