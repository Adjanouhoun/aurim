<?php

namespace App\Tests\Security;

use App\Entity\Market;
use App\Entity\User;
use App\Security\AdminMarketAccess;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class AdminMarketAccessTest extends TestCase
{
    public function testLocalAdministratorOnlyAccessesAssignedMarket(): void
    {
        $senegal = (new Market())->setCountryCode('SN')->setName('Sénégal')->setCurrencyCode('XOF');
        $mali = (new Market())->setCountryCode('ML')->setName('Mali')->setCurrencyCode('XOF');
        $user = (new User())->setEmail('senegal@example.com')->setMarket($senegal);

        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->with('ROLE_SUPER_ADMIN')->willReturn(false);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($token);

        $access = new AdminMarketAccess($authorizationChecker, $tokenStorage);

        self::assertSame([$senegal], $access->filterMarkets([$senegal, $mali]));
        self::assertTrue($access->canAccess($senegal));
        self::assertFalse($access->canAccess($mali));

        $this->expectException(AccessDeniedHttpException::class);
        $access->denyUnlessGranted($mali);
    }

    public function testSuperAdministratorAccessesEveryMarket(): void
    {
        $senegal = (new Market())->setCountryCode('SN')->setName('Sénégal')->setCurrencyCode('XOF');
        $mali = (new Market())->setCountryCode('ML')->setName('Mali')->setCurrencyCode('XOF');
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $authorizationChecker->method('isGranted')->with('ROLE_SUPER_ADMIN')->willReturn(true);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $access = new AdminMarketAccess($authorizationChecker, $tokenStorage);

        self::assertSame([$senegal, $mali], $access->filterMarkets([$senegal, $mali]));
        $access->denyUnlessGranted($mali);
    }
}
