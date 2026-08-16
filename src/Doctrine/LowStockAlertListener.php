<?php

namespace App\Doctrine;

use App\Entity\StockMovement;
use App\Notification\LowStockMailer;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class LowStockAlertListener
{
    /** @var array<int, StockMovement> */
    private array $pendingAlerts = [];

    public function __construct(private readonly LowStockMailer $mailer) {}

    public function onFlush(OnFlushEventArgs $event): void
    {
        foreach ($event->getObjectManager()->getUnitOfWork()->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof StockMovement
                || !$entity->triggersLowStockAlert()
                || !$entity->getProduct()->isActive()
                || !$entity->getWarehouse()->isActive()) {
                continue;
            }
            $this->pendingAlerts[spl_object_id($entity)] = $entity;
        }
    }

    public function postFlush(PostFlushEventArgs $event): void
    {
        if ([] === $this->pendingAlerts) {
            return;
        }

        $alerts = array_values($this->pendingAlerts);
        $this->pendingAlerts = [];
        $this->mailer->sendAlerts($alerts);
    }
}
