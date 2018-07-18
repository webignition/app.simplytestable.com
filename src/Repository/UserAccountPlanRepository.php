<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UserAccountPlanRepository extends EntityRepository
{

    public function findUserIdsWithPlan() {
        $queryBuilder = $this->createQueryBuilder('UserAccountPlan');
        $queryBuilder->select('User.id');
        $queryBuilder->leftJoin('App\Entity\User', 'User', 'WITH', 'User.id = UserAccountPlan.user');

        $result = $queryBuilder->getQuery()->getResult();

        $ids = array();

        foreach ($result as $idResult) {
            $ids[] = $idResult['id'];
        }

        return $ids;
    }
}