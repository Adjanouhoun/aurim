<?php

namespace App\Repository;

use App\Entity\Market;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<User> */
final class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /** @return list<User> */
    public function findOrderNotificationRecipients(Market $market): array
    {
        return array_values(array_filter(
            $this->findAll(),
            static function (User $user) use ($market): bool {
                $roles = $user->getRoles();
                if (in_array('ROLE_SUPER_ADMIN', $roles, true)) {
                    return true;
                }

                return in_array('ROLE_ADMIN', $roles, true)
                    && $user->getMarket()?->getId() === $market->getId();
            },
        ));
    }
}
