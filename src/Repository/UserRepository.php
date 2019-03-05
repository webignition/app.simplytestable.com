<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findAllNotWithIds(array $ids = []): array
    {
        $queryBuilder = $this->createQueryBuilder('User');
        $queryBuilder->select('User');

        if (count($ids)) {
            $queryBuilder->where('User.id NOT IN ('.  implode(',', $ids).')');
        }

        return $queryBuilder->getQuery()->getResult();
    }

}