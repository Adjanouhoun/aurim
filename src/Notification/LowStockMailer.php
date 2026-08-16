<?php

namespace App\Notification;

use App\Entity\StockMovement;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LowStockMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly string $senderAddress,
        private readonly string $adminAddress,
    ) {}

    /** @param list<StockMovement> $movements */
    public function sendAlerts(array $movements): void
    {
        if ([] === $movements) {
            return;
        }

        $countries = array_values(array_unique(array_map(
            static fn (StockMovement $movement): string => $movement->getWarehouse()->getMarket()->getName(),
            $movements,
        )));
        $subject = 1 === count($movements)
            ? sprintf('Alerte stock faible — %s', $movements[0]->getProduct()->getName())
            : sprintf('%d alertes de stock faible AURIM', count($movements));
        $countryCode = $movements[0]->getWarehouse()->getMarket()->getCountryCode();
        $email = (new TemplatedEmail())
            ->from(new Address($this->senderAddress, 'AURIM'))
            ->to($this->adminAddress)
            ->subject($subject)
            ->htmlTemplate('emails/admin_low_stock_alert.html.twig')
            ->context([
                'movements' => $movements,
                'countries' => $countries,
                'alertsUrl' => $this->urlGenerator->generate('admin_stock_alerts_by_market', ['pays' => $countryCode], UrlGeneratorInterface::ABSOLUTE_URL),
                'journalUrl' => $this->urlGenerator->generate('admin_stock_movements', ['pays' => $countryCode], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Échec de l’envoi d’une alerte de stock faible AURIM.', [
                'exception' => $exception,
                'products' => array_map(static fn (StockMovement $movement): string => $movement->getProduct()->getName(), $movements),
            ]);
        }
    }
}
