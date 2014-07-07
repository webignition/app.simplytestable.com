<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;

class TeamRepository extends EntityRepository {

    /**
     * @param $name
     * @return int
     */
    public function getTeamCountByName($name) {
        $queryBuilder = $this->createQueryBuilder('Team');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(Team.id) as total');
        $queryBuilder->where('Team.name = :TeamName');
        $queryBuilder->setParameter('TeamName', strtolower($name));

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }


    /**
     * @param User $leader
     * @return int
     */
    public function getTeamCountByLeader(User $leader) {
        $queryBuilder = $this->createQueryBuilder('Team');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(Team.id) as total');
        $queryBuilder->where('Team.leader = :Leader');
        $queryBuilder->setParameter('Leader', $leader);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }

}
