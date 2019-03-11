<?php

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Parameter;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\Task\Type\Type as TaskType;
use App\Entity\State;
use App\Entity\User;
use App\Entity\Task\Output as TaskOutput;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    const ISSUE_TYPE_ERROR = 'error';
    const ISSUE_TYPE_WARNING = 'warning';

    const FIELD_NAME_ERROR_COUNT = 'errorCount';
    const FIELD_NAME_WARNING_COUNT = 'warningCount';

    /**
     * @var array
     */
    private $issueTypeToIssueCountFieldNameMap = [
        self::ISSUE_TYPE_ERROR => self::FIELD_NAME_ERROR_COUNT,
        self::ISSUE_TYPE_WARNING => self::FIELD_NAME_WARNING_COUNT,
    ];

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
     * @param int|null $limit
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
     * @param string $issueType
     * @param State[] $statesToExclude
     * @return int
     */
    public function getCountWithIssuesByJob($job, $issueType, $statesToExclude)
    {
        $fieldName = $this->issueTypeToIssueCountFieldNameMap[$issueType];

        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('count(Task.id)');

        $wherePredicates = sprintf('Task.job = :Job AND TaskOutput.%s > 0', $fieldName);
        if (!empty($statesToExclude)) {
            $wherePredicates .= ' AND Task.state NOT IN (:StatesToExclude)';
        }

        $queryBuilder->where($wherePredicates);
        $queryBuilder->setParameter('Job', $job);

        if (!empty($statesToExclude)) {
            $queryBuilder->setParameter('StatesToExclude', $statesToExclude);
        }

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
     * @param string $fieldName
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

        $stateCondition = 'Task.state IN (:States)';
        $queryBuilder->setParameter('States', $states);

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
            $stateCondition
        ));

        $queryBuilder->setParameter('TaskType', $taskType);

        return $queryBuilder->getQuery()->getResult();
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
            $rawOutput = $item['output'];

            if (!is_null($rawOutput)) {
                $rawTaskOutputs[] = $rawOutput;
            }
        }

        return $rawTaskOutputs;
    }

    /**
     * @param User[] $users
     * @param State[] $states
     * @param string $periodStart
     * @param string $periodEnd
     *
     * @return int
     */
    public function getCountByUsersAndStatesForPeriod($users, $states, $periodStart, $periodEnd)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.id)');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->join('Task.job', 'Job');

        $userPredicates = '(Job.user IN (:Users))';
        $statePredicates = '(Task.state IN (:States))';
        $timePeriodPredicates = '(TimePeriod.startDateTime >= :PeriodStart and TimePeriod.startDateTime <= :PeriodEnd)';

        $queryBuilder->where(sprintf(
            '%s AND %s AND %s',
            $timePeriodPredicates,
            $userPredicates,
            $statePredicates
        ));

        $queryBuilder->setParameter('Users', $users);
        $queryBuilder->setParameter('States', $states);
        $queryBuilder->setParameter('PeriodStart', $periodStart);
        $queryBuilder->setParameter('PeriodEnd', $periodEnd);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }
}
