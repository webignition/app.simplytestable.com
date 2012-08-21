<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;

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
}