<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Product> */
final class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /** @return list<Product> */
    public function findActive(?string $categorySlug = null): array
    {
        $query = $this->createQueryBuilder('product')
            ->innerJoin('product.category', 'category')
            ->andWhere('product.active = :active')
            ->andWhere('category.active = :active')
            ->setParameter('active', true)
            ->orderBy('category.position', 'ASC')
            ->addOrderBy('product.id', 'ASC');

        if (null !== $categorySlug && '' !== $categorySlug) {
            $query->andWhere('category.slug = :categorySlug')->setParameter('categorySlug', $categorySlug);
        }

        return $query->getQuery()->getResult();
    }
}
