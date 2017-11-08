<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Team\Team;
use SimplyTestable\ApiBundle\Entity\User;

class TeamMemberRepository extends EntityRepository
{
    /**
     * @param User $user
     *
     * @return int
     */
    public function getMemberCountByUser(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('TeamMember');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TeamMember.id) as total');
        $queryBuilder->where('TeamMember.user = :User');
        $queryBuilder->setParameter('User', $user);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }

    /**
     * @param Team $team
     * @param User $user
     *
     * @return bool
     */
    public function getTeamContainsUser(Team $team, User $user)
    {
        $queryBuilder = $this->createQueryBuilder('TeamMember');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TeamMember.id) as total');
        $queryBuilder->where('TeamMember.user = :User AND TeamMember.team = :Team');
        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('Team', $team);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']) === 1;
    }
}

