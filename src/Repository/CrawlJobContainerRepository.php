<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Entity\User;

class CrawlJobContainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CrawlJobContainer::class);
    }

    /**
     * @param Task $task
     * @param State $state
     *
     * @return bool
     */
    public function doesCrawlTaskParentJobStateMatchState(Task $task, State $state)
    {
        $queryBuilder = $this->createQueryBuilder('CrawlJobContainer');
        $queryBuilder->join('CrawlJobContainer.parentJob', 'ParentJob');
        $queryBuilder->join('CrawlJobContainer.crawlJob', 'CrawlJob');
        $queryBuilder->join('ParentJob.state', 'State');

        $queryBuilder->select('State.name');

        $queryBuilder->where('CrawlJob = :CrawlJob');
        $queryBuilder->setParameter('CrawlJob', $task->getJob());
        $queryBuilder->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();

        return (empty($result))
            ? false
            : $result[0]['name'] == $state->getName();
    }

    /**
     * @param Job $job
     *
     * @return bool
     */
    public function hasForJob(Job $job)
    {
        return !is_null($this->getForJob($job));
    }

    /**
     * @param Job $job
     *
     * @return CrawlJobContainer
     */
    public function getForJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('CrawlJobContainer');
        $queryBuilder->select('CrawlJobContainer');
        $queryBuilder->join('CrawlJobContainer.parentJob', 'ParentJob');
        $queryBuilder->join('CrawlJobContainer.crawlJob', 'CrawlJob');

        $queryBuilder->where('ParentJob = :ParentJob OR CrawlJob = :CrawlJob');
        $queryBuilder->setParameter('ParentJob', $job);
        $queryBuilder->setParameter('CrawlJob', $job);

        $queryBuilder->setMaxResults(1);

        $result = $queryBuilder->getQuery()->getResult();
        return (count($result) === 0) ? null : $result[0];
    }

    /**
     * @param User $user
     * @param State[] $states
     *
     * @return CrawlJobContainer[]
     */
    public function getAllForUserByCrawlJobStates(User $user, $states)
    {
        if (empty($states)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('CrawlJobContainer');
        $queryBuilder->join('CrawlJobContainer.parentJob', 'ParentJob');
        $queryBuilder->join('CrawlJobContainer.crawlJob', 'CrawlJob');
        $queryBuilder->select('CrawlJobContainer');

        $stateWhereParts = array();

        $queryIndex = 0;

        foreach ($states as $stateIndex => $state) {
            $stateParameter = 'State' . $queryIndex;

            $stateWhereParts[] = 'CrawlJob.state = :' . $stateParameter;
            $queryBuilder->setParameter($stateParameter, $state);

            $queryIndex++;
        }

        $queryBuilder->where('CrawlJob.user = :User AND ('.implode(' OR ', $stateWhereParts).')');
        $queryBuilder->setParameter('User', $user);

        return $queryBuilder->getQuery()->getResult();
    }
}
