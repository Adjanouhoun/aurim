<?php

namespace App\Tests\Entity;

use App\Entity\PaymentMethod;
use PHPUnit\Framework\TestCase;

final class PaymentMethodTest extends TestCase
{
    public function testCashIsReadyWhenActive(): void
    {
        $method = (new PaymentMethod())->setType('cash')->setActive(true);

        self::assertTrue($method->isReadyForCheckout());
    }

    public function testMobileMoneyRequiresARecipientAccount(): void
    {
        $method = (new PaymentMethod())->setType('mobile_money_manual')->setActive(true);

        self::assertFalse($method->isReadyForCheckout());
        self::assertTrue($method->setRecipientAccount('46491521')->isReadyForCheckout());
        self::assertFalse($method->setActive(false)->isReadyForCheckout());
    }
}
