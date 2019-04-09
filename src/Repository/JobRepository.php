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
    public function getByStatesAndTaskStates($jobStates = [], $taskStates = [])
    {
        if (empty($jobStates) || empty($taskStates)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->join('Job.tasks', 'Tasks');
        $queryBuilder->select('DISTINCT Job');

        if (count($jobStates)) {
            $queryBuilder->andWhere('Job.state IN (:JobStates)')
                ->setParameter('JobStates', array_values($jobStates));
        }

        if (count($taskStates)) {
            $queryBuilder->andWhere('Tasks.state IN (:TaskStates)')
                ->setParameter('TaskStates', array_values($taskStates));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param State $state
     *
     * @return int[]
     */
    public function getIdsByState(State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');
        $queryBuilder->where('Job.state = :State');

        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        return $this->getSingleFieldCollectionFromResult($result, 'id');
    }

    /**
     * @param State $state
     *
     * @return int
     */
    public function getCountByState(State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('COUNT(Job.id)');
        $queryBuilder->where('Job.state = :State');

        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param User $user
     * @param JobType $jobType
     * @param WebSite $website
     * @param string $periodStart
     * @param string $periodEnd
     *
     * @return int
     */
    public function getJobCountByUserAndJobTypeAndWebsiteForPeriod(
        User $user,
        JobType $jobType,
        WebSite $website,
        $periodStart,
        $periodEnd
    ) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('count(Job.id)');
        $queryBuilder->join('Job.timePeriod', 'TimePeriod');

        $userPredicates = 'Job.user = :User';
        $typePredicates = 'Job.type = :JobType';
        $websitePredicates = 'Job.website = :Website';
        $timePeriodPredicates = 'TimePeriod.startDateTime >= :PeriodStart AND TimePeriod.startDateTime <= :PeriodEnd';

        $queryBuilder->where(
            sprintf(
                '%s AND %s AND %s AND (%s)',
                $userPredicates,
                $typePredicates,
                $websitePredicates,
                $timePeriodPredicates
            )
        );

        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('JobType', $jobType);
        $queryBuilder->setParameter('Website', $website);
        $queryBuilder->setParameter('PeriodStart', $periodStart);
        $queryBuilder->setParameter('PeriodEnd', $periodEnd);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param array $result
     * @param string $fieldName
     *
     * @return array
     */
    private function getSingleFieldCollectionFromResult($result, $fieldName)
    {
        $collection = array();

        foreach ($result as $resultItem) {
            $collection[] = $resultItem[$fieldName];
        }

        return $collection;
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
}
