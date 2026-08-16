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
use Symfony\Contracts\Translation\TranslatorInterface;

final class OrderMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly TranslatorInterface $translator,
        private readonly string $senderAddress,
        private readonly string $adminAddress,
    ) {}

    public function sendOrderCreated(CustomerOrder $order, Payment $payment): void
    {
        $confirmationPath = $this->urlGenerator->generate('app_order_confirmation', ['reference' => $order->getReference()]);
        $context = [
            'order' => $order,
            'payment' => $payment,
            'confirmationUrl' => $this->urlGenerator->generate(
                'app_locale_switch',
                ['locale' => $order->getLocale(), 'returnTo' => $confirmationPath],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject($this->trans('email.order.subject', $order, ['%reference%' => $order->getReference()]))
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->locale($order->getLocale())
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
            ->subject($this->trans('email.payment.subject', $order, ['%reference%' => $order->getReference()]))
            ->htmlTemplate('emails/payment_received.html.twig')
            ->locale($order->getLocale())
            ->context(['order' => $order, 'payment' => $payment]));
    }

    public function sendStatusChanged(CustomerOrder $order, string $status): void
    {
        $statusKey = match ($status) {
            'shipped' => 'pickup' === $order->getFulfillmentType() ? 'ready' : 'shipped',
            'preparing', 'delivered', 'cancelled', 'payment_failed', 'delivery_failed', 'refunded' => $status,
            default => null,
        };
        if (null === $statusKey) {
            return;
        }

        $parameters = ['%fulfillment%' => $order->getFulfillmentLabel()];
        $content = [
            'subject' => $this->trans('email.status.'.$statusKey.'.subject', $order, $parameters),
            'title' => $this->trans('email.status.'.$statusKey.'.title', $order, $parameters),
            'message' => $this->trans('email.status.'.$statusKey.'.message', $order, $parameters),
        ];

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject($content['subject'].' — '.$order->getReference())
            ->htmlTemplate('emails/order_status_changed.html.twig')
            ->locale($order->getLocale())
            ->context(['order' => $order, 'title' => $content['title'], 'message' => $content['message']]));
    }

    /** @param array<string, string> $parameters */
    private function trans(string $id, CustomerOrder $order, array $parameters = []): string
    {
        return $this->translator->trans($id, $parameters, locale: $order->getLocale());
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
