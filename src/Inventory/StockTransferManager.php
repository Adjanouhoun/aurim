<?php

namespace App\Inventory;

use App\Entity\Inventory;
use App\Entity\StockTransfer;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class StockTransferManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly StockMovementRecorder $movementRecorder,
    ) {}

    public function ship(StockTransfer $transfer): void
    {
        if (StockTransfer::STATUS_DRAFT !== $transfer->getStatus()) {
            throw new \DomainException('Ce transfert ne peut plus être expédié.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            $inventories = [];
            foreach ($transfer->getItems() as $item) {
                $inventory = $this->entityManager->getRepository(Inventory::class)->findOneBy([
                    'product' => $item->getProduct(),
                    'warehouse' => $transfer->getSourceWarehouse(),
                ]);
                if ($inventory instanceof Inventory) {
                    $this->entityManager->refresh($inventory, LockMode::PESSIMISTIC_WRITE);
                }
                if (!$inventory instanceof Inventory || $inventory->getAvailableQuantity() < $item->getQuantity()) {
                    throw new \DomainException(sprintf(
                        'Stock central insuffisant pour « %s » : %d unité(s) demandée(s).',
                        $item->getProduct()->getName(),
                        $item->getQuantity(),
                    ));
                }
                $inventories[(int) $item->getProduct()->getId()] = $inventory;
            }

            foreach ($transfer->getItems() as $item) {
                $inventory = $inventories[(int) $item->getProduct()->getId()];
                $inventory->setQuantityOnHand($inventory->getQuantityOnHand() - $item->getQuantity());
                $this->movementRecorder->record(
                    $inventory,
                    'transfer_shipped',
                    -$item->getQuantity(),
                    0,
                    sprintf('Expédition vers %s.', $transfer->getDestinationWarehouse()->getMarket()->getName()),
                    $transfer->getReference(),
                );
            }
            $transfer->markInTransit();
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function receive(StockTransfer $transfer): void
    {
        if (StockTransfer::STATUS_IN_TRANSIT !== $transfer->getStatus()) {
            throw new \DomainException('Ce transfert ne peut pas être réceptionné.');
        }

        $connection = $this->entityManager->getConnection();
        $connection->beginTransaction();
        try {
            foreach ($transfer->getItems() as $item) {
                $inventory = $this->entityManager->getRepository(Inventory::class)->findOneBy([
                    'product' => $item->getProduct(),
                    'warehouse' => $transfer->getDestinationWarehouse(),
                ]);
                if ($inventory instanceof Inventory) {
                    $this->entityManager->refresh($inventory, LockMode::PESSIMISTIC_WRITE);
                } else {
                    $inventory = (new Inventory())
                        ->setProduct($item->getProduct())
                        ->setWarehouse($transfer->getDestinationWarehouse());
                    $this->entityManager->persist($inventory);
                }
                $inventory->setQuantityOnHand($inventory->getQuantityOnHand() + $item->getQuantity());
                $this->movementRecorder->record(
                    $inventory,
                    'transfer_received',
                    $item->getQuantity(),
                    0,
                    sprintf('Réception depuis %s.', $transfer->getSourceWarehouse()->getName()),
                    $transfer->getReference(),
                );
            }
            $transfer->markReceived();
            $this->entityManager->flush();
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function cancel(StockTransfer $transfer): void
    {
        $transfer->cancel();
        $this->entityManager->flush();
    }
}
