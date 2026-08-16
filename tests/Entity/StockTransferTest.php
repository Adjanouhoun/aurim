<?php

namespace App\Tests\Entity;

use App\Entity\Product;
use App\Entity\StockTransfer;
use App\Entity\StockTransferItem;
use PHPUnit\Framework\TestCase;

final class StockTransferTest extends TestCase
{
    public function testTransferCalculatesItsTotalQuantity(): void
    {
        $transfer = (new StockTransfer())->setReference('TRF-TEST');
        $transfer
            ->addItem((new StockTransferItem())->setProduct(new Product())->setQuantity(3))
            ->addItem((new StockTransferItem())->setProduct(new Product())->setQuantity(5));

        self::assertSame(8, $transfer->getTotalQuantity());
        self::assertCount(2, $transfer->getItems());
    }

    public function testLifecycleFollowsDraftTransitReceivedSequence(): void
    {
        $transfer = (new StockTransfer())->setReference('TRF-TEST');

        self::assertSame(StockTransfer::STATUS_DRAFT, $transfer->getStatus());
        $transfer->markInTransit();
        self::assertSame(StockTransfer::STATUS_IN_TRANSIT, $transfer->getStatus());
        self::assertNotNull($transfer->getShippedAt());
        $transfer->markReceived();
        self::assertSame(StockTransfer::STATUS_RECEIVED, $transfer->getStatus());
        self::assertNotNull($transfer->getReceivedAt());
    }

    public function testOnlyDraftTransferCanBeCancelled(): void
    {
        $transfer = (new StockTransfer())->setReference('TRF-TEST');
        $transfer->markInTransit();

        $this->expectException(\DomainException::class);
        $transfer->cancel();
    }
}
