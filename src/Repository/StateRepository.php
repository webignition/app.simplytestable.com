<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class StateRepository extends EntityRepository
{

    public function findAllStartingWithAndExcluding($prefix, $excludeStates = array()) {
        $queryBuilder = $this->createQueryBuilder('State');
        $queryBuilder->select('State');

        $where = 'State.name LIKE :Prefix';

        if (is_array($excludeStates)) {
            foreach ($excludeStates as $stateIndex => $state) {
                $where .= ' AND State != :State' . $stateIndex;
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }
        }

        $queryBuilder->where($where);

        $queryBuilder->setParameter('Prefix', $prefix . '%');
        return $queryBuilder->getQuery()->getResult();
    }


}