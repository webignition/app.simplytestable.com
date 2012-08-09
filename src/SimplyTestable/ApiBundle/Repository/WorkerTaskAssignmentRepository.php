<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;

class WorkerTaskAssignmentRepository extends EntityRepository
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
}