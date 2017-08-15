<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\DBAL\Types\Type as DoctrineType;
use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;

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

        $taskIds = array();
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
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
     *
     * @return int
     */
    public function getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth(
        User $user,
        JobType $jobType,
        WebSite $website
    ) {
        $now = new \ExpressiveDate();

        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('count(Job.id)');
        $queryBuilder->join('Job.timePeriod', 'TimePeriod');

        $userPredicates = 'Job.user = :User';
        $typePredicates = 'Job.type = :JobType';
        $websitePredicates = 'Job.website = :Website';
        $timePeriodPredicates = 'TimePeriod.startDateTime >= :StartOfMonth AND TimePeriod.startDateTime <= :EndOfMonth';

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
        $queryBuilder->setParameter('StartOfMonth', $now->format('Y-m-01'));
        $queryBuilder->setParameter('EndOfMonth', $now->format('Y-m-'.$now->getDaysInMonth()).' 23:59:59');

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param User $user
     * @param JobType $type
     * @param State[] $excludeStates
     *
     * @return int[]
     */
    public function getIdsByUserAndTypeAndNotStates(User $user, JobType $type, $excludeStates = [])
    {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');

        $where = 'Job.user = :User and Job.type = :Type';

        if (is_array($excludeStates)) {
            foreach ($excludeStates as $stateIndex => $state) {
                $where .= ' AND Job.state != :State' . $stateIndex;
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }
        }

        $queryBuilder->where($where);
        $queryBuilder->orderBy('Job.id', 'asc');

        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('Type', $type);
        $result = $queryBuilder->getQuery()->getResult();

        return $this->getSingleFieldCollectionFromResult($result, 'id');
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

        if (count($result) === 0) {
            return false;
        }

        return $result[0]['isPublic'] === true;
    }
}
