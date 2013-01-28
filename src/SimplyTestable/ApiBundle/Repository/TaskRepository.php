<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type as TaskType;
use SimplyTestable\ApiBundle\Entity\State;

class TaskRepository extends EntityRepository
{
    
    /**
     * Get collection of all WorkerTaskAssignment entities ordered from oldest to
     * newest. Oldest is in position zero.
     * 
     * @return array
     */
    public function findAllOrderedByDateTime()
    {        
        return $this->getEntityManager()
            ->createQuery('SELECT p FROM SimplyTestableApiBundle:WorkerTaskAssignment p ORDER BY p.dateTime ASC')
            ->getResult();
    }
    
    
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
}
