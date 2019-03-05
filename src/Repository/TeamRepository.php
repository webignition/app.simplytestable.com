<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Entity\Team\Team;
use App\Entity\User;

class TeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Team::class);
    }

    public function getTeamCountByName(string $name): int
    {
        $queryBuilder = $this->createQueryBuilder('Team');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(Team.id) as total');
        $queryBuilder->where('LOWER(Team.name) = :TeamName');
        $queryBuilder->setParameter('TeamName', strtolower($name));

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }

    public function getTeamCountByLeader(User $leader): int
    {
        $queryBuilder = $this->createQueryBuilder('Team');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(Team.id) as total');
        $queryBuilder->where('Team.leader = :Leader');
        $queryBuilder->setParameter('Leader', $leader);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['total']);
    }

    public function getTeamByLeader(User $leader): ?Team
    {
        $queryBuilder = $this->createQueryBuilder('Team');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('Team');
        $queryBuilder->where('Team.leader = :Leader');
        $queryBuilder->setParameter('Leader', $leader);

        $result = $queryBuilder->getQuery()->getResult();

        return ($result[0] instanceof Team) ? $result[0] : null;
    }
}
