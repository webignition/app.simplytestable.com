<?php

namespace App\Repository;

use App\Entity\Team\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Entity\Team\Team;
use App\Entity\User;

class TeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Member::class);
    }

    public function getMemberCountByUser(User $user): int
    {
        $queryBuilder = $this->createQueryBuilder('TeamMember');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(TeamMember.id) as total');
        $queryBuilder->where('TeamMember.user = :User');
        $queryBuilder->setParameter('User', $user);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }

    public function getTeamContainsUser(Team $team, User $user): bool
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

