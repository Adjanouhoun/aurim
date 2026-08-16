<?php

namespace App\Security;

use App\Entity\Market;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class AdminMarketAccess
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {}

    public function isGlobal(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_SUPER_ADMIN');
    }

    public function assignedMarket(): ?Market
    {
        $user = $this->tokenStorage->getToken()?->getUser();

        return $user instanceof User ? $user->getMarket() : null;
    }

    public function canAccess(?Market $market): bool
    {
        if ($this->isGlobal()) {
            return true;
        }

        $assignedMarket = $this->assignedMarket();

        return $market instanceof Market
            && $assignedMarket instanceof Market
            && $assignedMarket->getCountryCode() === $market->getCountryCode();
    }

    public function denyUnlessGranted(?Market $market): void
    {
        if (!$this->canAccess($market)) {
            throw new AccessDeniedHttpException('Vous ne pouvez gérer que les données de votre marché.');
        }
    }

    /**
     * @param iterable<Market> $markets
     * @return list<Market>
     */
    public function filterMarkets(iterable $markets): array
    {
        $allowed = [];
        foreach ($markets as $market) {
            if ($this->canAccess($market)) {
                $allowed[] = $market;
            }
        }

        return $allowed;
    }
}
