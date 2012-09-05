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
}