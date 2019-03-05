<?php

namespace App\Repository;

use App\Entity\UserAccountPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class UserAccountPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAccountPlan::class);
    }

    public function findUserIdsWithPlan(): array
    {
        $queryBuilder = $this->createQueryBuilder('UserAccountPlan');
        $queryBuilder->select('User.id');
        $queryBuilder->leftJoin('App\Entity\User', 'User', 'WITH', 'User.id = UserAccountPlan.user');

        $result = $queryBuilder->getQuery()->getResult();

        $ids = [];

        foreach ($result as $idResult) {
            $ids[] = $idResult['id'];
        }

        return $ids;
    }
}