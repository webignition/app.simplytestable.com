<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
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
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.id) as task_type_total');
        $queryBuilder->where('Task.type = :Type');
        $queryBuilder->andWhere('Task.state = :State');
        $queryBuilder->setParameter('Type', $taskType);
        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)($result[0]['task_type_total']);
    }

    /**
     * @param Job $job
     * @param State $state
     *
     * @return int
     */
    public function getCountByJobAndState(Job $job, State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.id) as task_total');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->andWhere('Task.state = :State');
        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('State', $state);

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
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.state = :State');
        $queryBuilder->setParameter('State', $state);

        $result = $queryBuilder->getQuery()->getResult();

        $taskIds = array();
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getCountByJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.id) as task_total');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);

        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_total']);
    }

    /**
     * @param int[] $taskIds
     *
     * @return Task[]
     */
    public function getCollectionById($taskIds = [])
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');

        if (count($taskIds)) {
            $queryBuilder->where('Task.id IN ('.implode(',', $taskIds).')');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Job $job
     * @param int[] $taskIds
     *
     * @return Task[]
     */
    public function getCollectionByJobAndId(Job $job, $taskIds = [])
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');
        $queryBuilder->where('Task.job = :Job');

        if (count($taskIds)) {
            $queryBuilder->andWhere('Task.id IN ('.implode(',', $taskIds).')');
        }

        $queryBuilder->setParameter('Job', $job);

        return $queryBuilder->getQuery()->getResult();
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
     * @param int $limit
     *
     * @return int[]
     */
    public function getIdsByJob(Job $job, $limit = 0)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $result = $queryBuilder->getQuery()->getResult();

        $taskIds = array();
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @param Job $job
     * @param State[] $taskStates
     * @param int $limit
     *
     * @return int[]
     */
    public function getIdsByJobAndTaskStates(Job $job, $taskStates = [], $limit = 0)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);

        if (count($taskStates)) {
            $queryBuilder->andWhere('Task.state IN (:TaskStates)')
                ->setParameter('TaskStates', $taskStates);
        }

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $result = $queryBuilder->getQuery()->getResult();

        $taskIds = array();
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @param Job $job
     * @param string[] $urlSet
     *
     * @return int[]
     */
    public function getIdsByJobAndUrlExclusionSet(Job $job, $urlSet)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');

        $urlParameterList = array();
        foreach ($urlSet as $urlIndex => $url) {
            $urlParameterList[] = ':Url' . $urlIndex.'';
        }

        $queryBuilder->where('Task.job = :Job AND Task.url NOT IN ('.implode(', ', $urlParameterList).')');

        $queryBuilder->setParameter('Job', $job);
        foreach ($urlSet as $urlIndex => $url) {
            $queryBuilder->setParameter('Url' . $urlIndex, $url);
        }

        $result = $queryBuilder->getQuery()->getResult();

        $taskIds = array();
        foreach ($result as $taskId) {
            $taskIds[] = $taskId['id'];
        }

        return $taskIds;
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getErroredCountByJob(Job $job, $excludeStates = null)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('count(Task.id)');

        $where = 'Task.job = :Job AND TaskOutput.errorCount > :ErrorCount';

        if (is_array($excludeStates)) {
            foreach ($excludeStates as $stateIndex => $state) {
                $where .= ' AND Task.state != :State' . $stateIndex;
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }
        }

        $queryBuilder->where($where);

        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('ErrorCount', 0);

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
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('sum(TaskOutput.errorCount)');

        $where = 'Task.job = :Job AND TaskOutput.errorCount > :ErrorCount';

        $queryBuilder->where($where);

        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('ErrorCount', 0);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getWarningedCountByJob(Job $job, $excludeStates = null)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('count(Task.id)');

        $where = 'Task.job = :Job AND TaskOutput.warningCount > :WarningCount';

        if (is_array($excludeStates)) {
            foreach ($excludeStates as $stateIndex => $state) {
                $where .= ' AND Task.state != :State' . $stateIndex;
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }
        }

        $queryBuilder->where($where);

        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('WarningCount', 0);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Job $job
     *
     * @return int
     */
    public function getWarningCountByJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('sum(TaskOutput.warningCount)');

        $where = 'Task.job = :Job AND TaskOutput.warningCount > :WarningCount';

        $queryBuilder->where($where);

        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('WarningCount', 0);

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
    }

    /**
     * @param Job $job
     * @param State[] $states
     *
     * @return int
     */
    public function getTaskCountByState(Job $job, $states)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('count(Task.id)');

        $stateConditions = array();

        foreach ($states as $stateIndex => $state) {
            $stateConditions[] = '(Task.state = :State'.$stateIndex.') ';
            $queryBuilder->setParameter('State'.$stateIndex, $state);
        }

        $queryBuilder->where('(Task.job = :Job AND ('.implode('OR', $stateConditions).'))');
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

        foreach ($states as $stateIndex => $state) {
            $stateConditions[] = '(Task.state = :State'.$stateIndex.') ';
            $queryBuilder->setParameter('State'.$stateIndex, $state);
        }

        $urlConditions = array();

        foreach ($urlSet as $urlIndex => $url) {
            $urlConditions[] = 'Task.url = :Url' . $urlIndex;
            $queryBuilder->setParameter('Url' . $urlIndex, $url);
        }

        $queryBuilder->where(
            '('.implode(' OR ', $urlConditions).') and Task.type = :TaskType AND ('.implode('OR ', $stateConditions).')'
        );

        $queryBuilder->setParameter('TaskType', $taskType);
        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return int[]
     */
    public function findUsedTaskOutputIds()
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('DISTINCT TaskOutput.id as TaskOutputId');

        $result = $queryBuilder->getQuery()->getResult();

        if (count($result) === 0) {
            return array();
        }

        $ids = array();

        foreach ($result as $taskOutputIdResult) {
            $ids[] = $taskOutputIdResult['TaskOutputId'];
        }

        return $ids;
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

        if (count($result) === 0) {
            return [];
        }

        $ids = [];

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
     * @param User $user
     * @param State[] $states
     *
     * @return int
     */
    public function getCountByUserAndStatesForCurrentMonth(User $user, $states)
    {
        $now = new \ExpressiveDate();

        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.id)');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->join('Task.job', 'Job');

        $where = 'Job.user = :User AND (TimePeriod.startDateTime >= :StartOfMonth and TimePeriod.startDateTime <= :EndOfMonth)';

        if (is_array($states)) {
            $stateWhereParts = array();

            foreach ($states as $stateIndex => $state) {
                $stateWhereParts[] = ' Task.state = :State'.$stateIndex.' ';
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }

            $where .= ' AND ('.  implode('OR', $stateWhereParts).')';
        }

        $queryBuilder->where($where);
        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('StartOfMonth', $now->format('Y-m-01'));
        $queryBuilder->setParameter('EndOfMonth', $now->format('Y-m-'.$now->getDaysInMonth()).' 23:59:59');

        $result = $queryBuilder->getQuery()->getResult();

        return (int)$result[0][1];
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
