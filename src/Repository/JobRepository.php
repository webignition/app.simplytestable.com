<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Entity\Job\Job;
use App\Entity\Job\Type as JobType;
use App\Entity\State;
use App\Entity\WebSite;
use App\Entity\User;
use FOS\UserBundle\Model\UserInterface;

class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @param State[] $jobStates
     * @param State[] $taskStates
     *
     * @return Job[]
     */
    public function getByStatesAndTaskStates(array $jobStates, array $taskStates): array
    {
        if (empty($jobStates) || empty($taskStates)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->join('Job.tasks', 'Tasks');
        $queryBuilder->select('DISTINCT Job');

        $queryBuilder->andWhere('Job.state IN (:JobStates)');
        $queryBuilder->andWhere('Tasks.state IN (:TaskStates)');

        $queryBuilder->setParameters([
            'JobStates' => array_values($jobStates),
            'TaskStates' => array_values($taskStates),
        ]);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param State $state
     *
     * @return int[]
     */
    public function getIdsByState(State $state): array
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');
        $queryBuilder->where('Job.state = :State');

        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        return $this->getSingleFieldCollectionFromResult($result, 'id');
    }

    public function getCountByState(State $state): int
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('COUNT(Job.id)');
        $queryBuilder->where('Job.state = :State');

        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        return (int) $result[0][1];
    }

    public function getJobCountByUserAndJobTypeAndWebsiteForPeriod(
        User $user,
        JobType $jobType,
        WebSite $website,
        string $periodStart,
        string $periodEnd
    ): int {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('count(Job.id)');
        $queryBuilder->join('Job.timePeriod', 'TimePeriod');

        $queryBuilder->where('Job.user = :User');
        $queryBuilder->andWhere('Job.type = :JobType');
        $queryBuilder->andWhere('Job.website = :Website');
        $queryBuilder->andWhere('TimePeriod.startDateTime >= :PeriodStart AND TimePeriod.startDateTime <= :PeriodEnd');

        $queryBuilder->setParameters([
            'User' => $user,
            'JobType' => $jobType,
            'Website' =>$website,
            'PeriodStart' => $periodStart,
            'PeriodEnd' => $periodEnd,
        ]);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    public function exists(int $jobId): bool
    {
        return $this->checkJobExistence($jobId);
    }

    public function isOwnedByUser(UserInterface $user, int $jobId): bool
    {
        return $this->isOwnedByUsers([$user], $jobId);
    }

    public function isOwnedByUsers(array $users, int $jobId): bool
    {
        return $this->checkJobExistence(
            $jobId,
            [
                'Job.user IN (:Users)'
            ],
            [
                'Users' => $users,
            ]
        );
    }

    public function isPublic(int $jobId): bool
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.isPublic');
        $queryBuilder->where('Job.id = :JobId');
        $queryBuilder->setParameter('JobId', $jobId);

        $result = $queryBuilder->getQuery()->getResult();

        return !!$result[0]['isPublic'];
    }

    public function findJobsForUserOlderThanMaxAgeWithStates(User $user, string $maximumAge, array $states = [])
    {
        $queryBuilder = $this->createQueryBuilder('Job');

        $queryBuilder->select('Job');
        $queryBuilder->join('Job.timePeriod', 'TimePeriod');

        $queryBuilder->where('Job.user = :User');
        $queryBuilder->andWhere('TimePeriod.startDateTime < :MaximumAge');

        $parameters = [
            'User' => $user,
            'MaximumAge' => new \DateTimeImmutable('-' . $maximumAge),
        ];

        if (count($states)) {
            $queryBuilder->andWhere('Job.state IN (:States)');
            $parameters['States'] = $states;
        }

        $queryBuilder->setParameters($parameters);

        $result = $queryBuilder->getQuery()->getResult();

        return $result;
    }

    private function checkJobExistence(int $jobId, array $wherePredicates = [], array $parameters = []): bool
    {
        $wherePredicates[] = 'Job.id = :JobId';
        $parameters['JobId'] = $jobId;

        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');

        foreach ($wherePredicates as $predicate) {
            $queryBuilder->andWhere($predicate);
        }

        $queryBuilder->setParameters($parameters);

        $result = $queryBuilder->getQuery()->getResult();

        return !empty($result);
    }

    private function getSingleFieldCollectionFromResult(array $result, string $fieldName): array
    {
        $collection = array();

        foreach ($result as $resultItem) {
            $collection[] = $resultItem[$fieldName];
        }

        return $collection;
    }
}
