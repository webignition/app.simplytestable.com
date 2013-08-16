<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\User;

class TaskRepository extends EntityRepository
{    

    /**
     * Get the total number of URLs covered by a Job
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
     *
     * @param Job $job
     * @return array
     */
    public function findUrlsByJob(Job $job)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('DISTINCT Task.url');
        $queryBuilder->where('Task.job = :Job');
        $queryBuilder->setParameter('Job', $job);        
        
        return $queryBuilder->getQuery()->getArrayResult();
    }
    
    public function findUrlsByJobAndState(Job $job, State $state) {        
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
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @param string $url
     * @return boolean
     */
    public function findUrlExistsByJobAndUrl(Job $job, $url) {        
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('COUNT(Task.url)');
        $queryBuilder->where('Task.job = :Job AND Task.url = :Url');
        $queryBuilder->setParameter('Job', $job);
        $queryBuilder->setParameter('Url', $url);

        $result = $queryBuilder->getQuery()->getResult();        
        
        return $result[0][1] == 1;
    }
    
    
    
    /**
     *
     * @param TaskType $taskType
     * @param State $state
     * @return integer 
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
     *
     * @param Job $job
     * @param State $state
     * @return integer 
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
     *
     * @param Job $job
     * @param State $state
     * @return array 
     */
    public function getByJobAndStates(Job $job, $states)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');
        
        $where = 'Task.job = :Job AND ';
        $stateWhereParts = '';        

        foreach ($states as $stateIndex => $state) {
            $stateWhereParts[] = 'Task.state = :State' . $stateIndex;
            $queryBuilder->setParameter('State'.$stateIndex, $state);
        }
        
        $where .= '(' . implode(' OR ', $stateWhereParts) . ')';

        
        $queryBuilder->where($where);        

        $queryBuilder->setParameter('Job', $job);
        
        return $queryBuilder->getQuery()->getResult();      
    }      
    

    /**
     *
     * @param State $state
     * @return integer 
     */
    public function getCountByState(State $state)
    {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->setMaxResults(1);
        $queryBuilder->select('count(DISTINCT Task.id) as task_total');
        $queryBuilder->where('Task.state = :State');
        $queryBuilder->setParameter('State', $state);
        
        $result = $queryBuilder->getQuery()->getResult();
        return (int)($result[0]['task_total']);        
    }     
    
    
    /**
     *
     * @param State $state
     * @return integer 
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
     *
     * @param Job $job
     * @return integer 
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
   

    public function getCollectionById($taskIds = array()) {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');
        
        if (count($taskIds)) {
            $queryBuilder->where('Task.id IN ('.implode(',', $taskIds).')');
        }        
        
        return $queryBuilder->getQuery()->getResult();      
    }    
    
    public function getCollectionByJobAndId(Job $job, $taskIds = array()) {
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
     *
     * @param Job $job
     * @return array 
     */
    public function getIdsByJob(Job $job) {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task.id');
        $queryBuilder->where('Task.job = :Job');        
        $queryBuilder->setParameter('Job', $job);
        
        $repostitoryResult = $queryBuilder->getQuery()->getResult();
        
        $taskIds = array();
        foreach ($repostitoryResult as $taskId) {
            $taskIds[] = $taskId['id'];
        }
        
        return $taskIds;
    }
    
    /**
     *
     * @param Job $job
     * @return array 
     */
    public function getIdsByJobAndUrlExclusionSet(Job $job, $urlSet) {
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
        
        $repostitoryResult = $queryBuilder->getQuery()->getResult();
        
        $taskIds = array();
        foreach ($repostitoryResult as $taskId) {
            $taskIds[] = $taskId['id'];
        }
        
        return $taskIds;
    }    
    
    
    /**
     *
     * @param Job $job
     * @return int
     */
    public function getErrorCountByJob(Job $job, $excludeStates = null) {        
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
    
    
    
    public function getTaskCountByState(Job $job, $states) {
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
    
    
    public function getCollectionByUrlAndTaskTypeAndStates($url, TaskType $taskType, $states) {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->select('Task');

        $stateConditions = array();

        foreach ($states as $stateIndex => $state) {
            $stateConditions[] = '(Task.state = :State'.$stateIndex.') ';
            $queryBuilder->setParameter('State'.$stateIndex, $state);
        }        
        
        $queryBuilder->where('Task.url = :Url and Task.type = :TaskType AND ('.implode('OR ', $stateConditions).')');        
        $queryBuilder->setParameter('Url', $url);
        $queryBuilder->setParameter('TaskType', $taskType);
        
        return $queryBuilder->getQuery()->getResult();       
    }
    
    
    public function findUsedTaskOutputIds() {
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
    
    
    public function getTaskOutputByType(TaskType $type) {        
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.output', 'TaskOutput');
        $queryBuilder->select('DISTINCT TaskOutput.id as TaskOutputId');        
        $queryBuilder->where('Task.type = :Type');
        $queryBuilder->setParameter('Type', $type);
        $queryBuilder->orderBy('TaskOutputId','ASC');
        
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
     * 
     * @param \DateTime $sinceDatetime
     * @return int
     */
    public function getThroughputSince(\DateTime $sinceDatetime) {
        $queryBuilder = $this->createQueryBuilder('Task');
        $queryBuilder->join('Task.timePeriod', 'TimePeriod');
        $queryBuilder->select('COUNT(Task.id)');        
        $queryBuilder->where('TimePeriod.endDateTime > :SinceDateTime');
        $queryBuilder->setParameter('SinceDateTime', $sinceDatetime);
        
        $result = $queryBuilder->getQuery()->getResult(); 
        
        return (int)$result[0][1];       
    }
    
    
    public function getCountByUserAndStatesForCurrentMonth(User $user, $states) {
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
        $queryBuilder->setParameter('EndOfMonth', $now->format('Y-m-'.$now->getDaysInMonth()));        
        
        $result = $queryBuilder->getQuery()->getResult();
        
        return (int)$result[0][1];
        
/**
SELECT COUNT( Task.id ) 
FROM Task
LEFT JOIN Job ON Task.job_id = Job.id
LEFT JOIN fos_user ON Job.user_id = fos_user.id
LEFT JOIN State ON Task.state_id = State.id
LEFT JOIN TimePeriod ON Task.timePeriod_id = TimePeriod.id
WHERE fos_user.id =3
AND State.name
IN (
'task-completed',  'task-failed-no-retry-available',  'task-failed-retry-available',  'task-failed-retry-limit-reached',  'task-skipped'
)
AND TimePeriod.startDateTime >=  '2013-06-01'
AND TimePeriod.startDateTime <=  '2013-06-30'
 */      
    }
}
