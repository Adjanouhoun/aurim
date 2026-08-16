<?php

namespace App\Notification;

use App\Entity\CustomerOrder;
use App\Entity\Payment;
use App\Repository\UserRepository;
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
        private readonly UserRepository $users,
        private readonly string $senderAddress,
        private readonly string $adminAddress,
    ) {}

    public function sendOrderCreated(CustomerOrder $order, Payment $payment): void
    {
        $context = $this->orderContext($order, $payment);

        $this->send((new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to(new Address($order->getEmail(), $order->getCustomerName()))
            ->subject($this->trans('email.order.subject', $order, ['%reference%' => $order->getReference()]))
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->locale($order->getLocale())
            ->context($context));

        $this->sendAdminOrderCreated($order, $payment);
    }

    public function sendAdminOrderCreated(CustomerOrder $order, Payment $payment): void
    {
        $context = $this->orderContext($order, $payment);

        foreach ($this->adminRecipients($order) as $recipient) {
            $this->send((new TemplatedEmail())
                ->from(new Address($this->senderAddress, 'AURIM'))
                ->to($recipient)
                ->replyTo($order->getEmail())
                ->subject('Nouvelle commande '.$order->getReference())
                ->htmlTemplate('emails/admin_new_order.html.twig')
                ->context($context));
        }
    }

    /** @return array{order: CustomerOrder, payment: Payment, confirmationUrl: string} */
    private function orderContext(CustomerOrder $order, Payment $payment): array
    {
        $confirmationPath = $this->urlGenerator->generate('app_order_confirmation', ['reference' => $order->getReference()]);

        return [
            'order' => $order,
            'payment' => $payment,
            'confirmationUrl' => $this->urlGenerator->generate(
                'app_locale_switch',
                ['locale' => $order->getLocale(), 'returnTo' => $confirmationPath],
                UrlGeneratorInterface::ABSOLUTE_URL,
            ),
        ];
    }

    /** @return list<Address> */
    private function adminRecipients(CustomerOrder $order): array
    {
        $addresses = [];
        foreach ($this->users->findOrderNotificationRecipients($order->getMarket()) as $user) {
            $email = mb_strtolower($user->getEmail());
            $addresses[$email] = new Address($user->getEmail());
        }

        if ([] === $addresses) {
            $addresses[mb_strtolower($this->adminAddress)] = new Address($this->adminAddress);
        }

        return array_values($addresses);
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
