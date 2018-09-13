<?php
namespace App\Repository;

use App\Entity\Task\Type\Type as TaskType;
use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\EntityRepository;
use App\Entity\Job\Job;
use App\Entity\Job\Type as JobType;
use App\Entity\State;
use App\Entity\WebSite;
use App\Entity\User;

class JobRepository extends EntityRepository
{
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

    /**
     * @param int $jobId
     *
     * @return bool
     */
    public function getIsPublicByJobId($jobId)
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.isPublic');

        $queryBuilder->where('Job.id = :JobId');
        $queryBuilder->setParameter('JobId', $jobId, DoctrineType::INTEGER);

        $result = $queryBuilder->getQuery()->getResult();

        if (empty($result)) {
            return false;
        }

        return $result[0]['isPublic'] === true;
    }

    /**
     * @param TaskType $jsTaskType
     *
     * @return int[]
     */
    public function getIdsForJobsWithJsJobTaskTypeAndNoTasks(TaskType $jsTaskType)
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('DISTINCT Job.id');
        $queryBuilder->where(':TaskType MEMBER OF Job.requestedTaskTypes');
        $queryBuilder->andWhere('Job.tasks IS EMPTY');

        $queryBuilder->setParameter('TaskType', $jsTaskType);

        $results = $queryBuilder->getQuery()->getResult();

        $ids = [];

        foreach ($results as $result) {
            $ids[] = $result['id'];
        }

        return $ids;
    }
}
