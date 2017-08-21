<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\Task\Output as TaskOutput;

class TaskRepository extends EntityRepository
{
    /**
     * @param Job $job
     *
     * @return int
     */
    public function findUrlCountByJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.url) as url_total');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['url_total']);
    }

    /**
     * @param Job $job
     *
     * @return string[]
     */
    public function findUrlsByJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('DISTINCT Task.url');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * @param Job $job
     * @param State $state
     *
     * @return string[]
     */
    public function findUrlsByJobAndState(Job $job, State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('DISTINCT Task.url');
        $queryBuilder->where('Task.job = :Job AND Task.state = :State');
        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('State', $state);

        $urls = array();
        $result = $queryBuilder->getQuery()->getResult();

        foreach ($result as $item) {
            $urls[] = $item['url'];
        }

        return $urls;
    }
    /**
     * @param Job $job
     * @param string $url
     *
     * @return bool
     */
    public function findUrlExistsByJobAndUrl(Job $job, $url)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.url)');
        $queryBuilder->where('Task.job = :Job AND Task.url = :Url');
        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('Url', $url);

        $result = $queryBuilder->getQuery()->getResult();

        return $result[0][1] > 0;
    }

    /**
     * @param TaskType $taskType
     * @param State $state
     *
     * @return int
     */
    public function getCountByTaskTypeAndState(TaskType $taskType, State $state)
    {
        return $this->getCountBy(
            'Task.type = :Type AND Task.state = :State',
            new ArrayCollection([
                new Parameter('Type', $taskType),
                new Parameter('State', $state),
            ])
        );
    }

    /**
     * @param Job $job
     * @param State[] $states
     *
     * @return int
     */
    public function getCountByJobAndStates(Job $job, $states)
    {
        return $this->getCountBy(
            'Task.job = :Job AND Task.state IN (:State)',
            new ArrayCollection([
                new Parameter('Job', $job),
                new Parameter('State', $states),
            ])
        );
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCountByJob(Job $job)
    {
        return $this->getCountBy(
            'Task.job = :Job',
            new ArrayCollection([
                new Parameter('Job', $job),
            ])
        );
    }

    /**
     * @param string $wherePredicates
     * @param ArrayCollection $parameters
     *
     * @return int
     */
    private function getCountBy($wherePredicates, ArrayCollection $parameters)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.id) as task_total');
        $queryBuilder->where($wherePredicates);
        $queryBuilder->setParameters($parameters);

        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_total']);
    }

    /**
     * @param State $state
     *
     * @return int[]
     */
    public function getIdsByState(State $state)
    {
        return $this->getIdsBy(
            'Task.state = :State',
            new ArrayCollection([
                new Parameter('State', $state),
            ])
        );
    }

    /**
     * @param string $wherePredicates
     * @param ArrayCollection $parameters
     *
     * @return int[]
     */
    private function getIdsBy($wherePredicates, ArrayCollection $parameters, $limit = null)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where($wherePredicates);
        $queryBuilder->setParameters($parameters);

        if (!empty($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        $result = $queryBuilder->getQuery()->getResult();

        $taskIds = [];
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @param Job $job
     * @param State $state
     *
     * @return TaskOutput[]
     */
    public function getOutputCollectionByJobAndState(Job $job, State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');

        $queryBuilder->select('TaskOutput.output');
        $queryBuilder->where('Task.job = :Job AND Task.state = :State and TaskOutput.errorCount = 0');

        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();
        $rawTaskOutputs = array();

        foreach ($result as $item) {
            $rawTaskOutputs[] = $item['output'];
        }

        return $rawTaskOutputs;
    }

    /**
     * @param Job $job
     *
     * @return int[]
     */
    public function getIdsByJob(Job $job)
    {
        return $this->getIdsBy(
            'Task.job = :Job',
            new ArrayCollection([
                new Parameter('Job', $job),
            ])
        );
    }

    /**
     * @param Job $job
     * @param State[] $states
     * @param int $limit
     *
     * @return int[]
     */
    public function getIdsByJobAndStates(Job $job, $states = [], $limit = 0)
    {
        return $this->getIdsBy(
            'Task.job = :Job AND Task.state IN (:State)',
            new ArrayCollection([
                new Parameter('Job', $job),
                new Parameter('State', $states),
            ]),
            $limit
        );
    }

    /**
     * @param Job $job
     * @param string[] $urlSet
     *
     * @return int[]
     */
    public function getIdsByJobAndUrlExclusionSet(Job $job, $urlSet)
    {
        $wherePredicates = 'Task.job = :Job';
        $parameters = new ArrayCollection([
            new Parameter('Job', $job),
        ]);

        if (!empty($urlSet)) {
            $wherePredicates .= ' AND Task.url NOT IN (:UrlSet)';
            $parameters->add(new Parameter('UrlSet', $urlSet));
        }

        return $this->getIdsBy($wherePredicates, $parameters);
    }

    /**
     * @param Job $job
     * @param State[] $statesToExclude
     *
     * @return int
     */
    public function getErroredCountByJob(Job $job, $statesToExclude)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('count(Task.id)');

        $wherePredicates = 'Task.job = :Job AND TaskOutput.errorCount > 0';

        $stateIndex = 0;
        foreach ($statesToExclude as $state) {
            $wherePredicates .= ' AND Task.state != :State' . $stateIndex;
            $queryBuilder->setParameter('State'.$stateIndex, $state);
            $stateIndex++;
        }

        $queryBuilder->where($wherePredicates);

        $queryBuilder->setParameter('Job', $job);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getErrorCountByJob(Job $job)
    {
        return $this->getIssueCountByJob($job, 'errorCount');
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getWarningCountByJob(Job $job)
    {
        return $this->getIssueCountByJob($job, 'warningCount');
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    private function getIssueCountByJob($job, $fieldName)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select(sprintf('sum(TaskOutput.%s)', $fieldName));

        $queryBuilder->where(sprintf(
            'Task.job = :Job AND TaskOutput.%s > 0',
            $fieldName
        ));
        $queryBuilder->setParameter('Job', $job);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Job $job
     * @param State[] $statesToExclude
     *
     * @return int
     */
    public function getWarningedCountByJob(Job $job, $statesToExclude)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('count(Task.id)');

        $where = 'Task.job = :Job AND TaskOutput.warningCount > 0';

        $stateIndex = 0;
        foreach ($statesToExclude as $state) {
            $where .= ' AND Task.state != :State' . $stateIndex;
            $queryBuilder->setParameter('State'.$stateIndex, $state);
            $stateIndex++;
        }

        $queryBuilder->where($where);
        $queryBuilder->setParameter('Job', $job);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param string[] $urlSet
     * @param TaskType $taskType
     * @param State[] $states
     *
     * @return Task[]
     */
    public function getCollectionByUrlSetAndTaskTypeAndStates($urlSet, TaskType $taskType, $states)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');

        $stateConditions = array();

        $stateIndex = 0;
        foreach ($states as $state) {
            $stateConditions[] = '(Task.state = :State'.$stateIndex.')';
            $queryBuilder->setParameter('State'.$stateIndex, $state);
            $stateIndex++;
        }

        $urlConditions = array();

        $urlIndex = 0;
        foreach ($urlSet as $url) {
            $urlConditions[] = 'Task.url = :Url' . $urlIndex;
            $queryBuilder->setParameter('Url' . $urlIndex, $url);
            $urlIndex++;
        }

        $queryBuilder->where(sprintf(
            'Task.type = :TaskType AND (%s) AND (%s)',
            implode(' OR ', $urlConditions),
            implode(' OR ', $stateConditions)

        ));

        $queryBuilder->setParameter('TaskType', $taskType);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param TaskType $type
     *
     * @return int[]
     */
    public function getTaskOutputByType(TaskType $type)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('DISTINCT TaskOutput.id as TaskOutputId');
        $queryBuilder->where('Task.type = :Type');
        $queryBuilder->setParameter('Type', $type);
        $queryBuilder->orderBy('TaskOutputId', 'ASC');

        $result = $queryBuilder->getQuery()->getResult();

        $ids = [];

        if (empty($result)) {
            return $ids;
        }

        foreach ($result as $taskOutputIdResult) {
            $ids[] = $taskOutputIdResult['TaskOutputId'];
        }

        return $ids;
    }

    /**
     * @param \DateTime $sinceDatetime
     *
     * @return int
     */
    public function getThroughputSince(\DateTime $sinceDatetime)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->select('COUNT(Task.id)');
        $queryBuilder->where('TimePeriod.endDateTime > :SinceDateTime');
        $queryBuilder->setParameter('SinceDateTime', $sinceDatetime);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Task $task
     * @param int $limit
     *
     * @return string[]
     */
    public function findOutputByJobAndType(Task $task, $limit = null)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->join('Task.job', 'Job');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');

        $queryBuilder->select('TaskOutput.output');
        $queryBuilder->where('Job = :Job AND Task.type = :Type');
        $queryBuilder->orderBy('TaskOutput.id', 'DESC');

        if (!is_null($limit)) {
            $queryBuilder->setMaxResults(100);
        }

        $queryBuilder->setParameter('Job', $task->getJob());
        $queryBuilder->setParameter('Type', $task->getType());

        $result = $queryBuilder->getQuery()->getResult();
        $rawTaskOutputs = array();

        foreach ($result as $item) {
            $rawTaskOutputs[] = $item['output'];
        }

        return $rawTaskOutputs;
    }

    /**
     * @param User[] $users
     * @param State[] $states
     *
     * @return int
     */
    public function getCountByUsersAndStatesForCurrentMonth($users, $states)
    {
        $now = new \ExpressiveDate();

        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.id)');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->join('Task.job', 'Job');

        $where = ' (TimePeriod.startDateTime >= :StartOfMonth and TimePeriod.startDateTime <= :EndOfMonth)';

        $userWhereParts = array();

        foreach ($users as $userIndex => $user) {
            $userWhereParts[] = ' Job.user = :User'.$userIndex.' ';
            $queryBuilder->setParameter('User'.$userIndex, $user);
        }

        $where .= ' AND ('.  implode('OR', $userWhereParts).')';

        if (is_array($states)) {
            $stateWhereParts = array();

            foreach ($states as $stateIndex => $state) {
                $stateWhereParts[] = ' Task.state = :State'.$stateIndex.' ';
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }

            $where .= ' AND ('.  implode('OR', $stateWhereParts).')';
        }

        $queryBuilder->where($where);
        $queryBuilder->setParameter('StartOfMonth', $now->format('Y-m-01'));
        $queryBuilder->setParameter('EndOfMonth', $now->format('Y-m-'.$now->getDaysInMonth()).' 23:59:59');

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }
}
