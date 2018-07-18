<?php
namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class UserRepository extends EntityRepository
{

    /**
     *
     * @param array $ids
     * @return array
     */
    public function findAllNotWithIds($ids = array()) {
        $queryBuilder = $this->createQueryBuilder('User');
        $queryBuilder->select('User');

        if (count($ids)) {
            $queryBuilder->where('User.id NOT IN ('.  implode(',', $ids).')');
        }

        return $queryBuilder->getQuery()->getResult();
    }

}