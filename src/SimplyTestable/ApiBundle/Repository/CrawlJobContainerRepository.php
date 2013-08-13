<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
//use SimplyTestable\ApiBundle\Entity\State;
//use SimplyTestable\ApiBundle\Entity\Task\Task;
//use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;

class CrawlJobContainerRepository extends EntityRepository
{
    
    public function findAllByJobAndStates(Job $job, $states) {
        $queryBuilder = $this->createQueryBuilder('CrawlJobContainer');
        $queryBuilder->select('CrawlJobContainer');
        $queryBuilder->join('CrawlJobContainer.parentJob', 'ParentJob');
        
        $where = 'ParentJob = :Job';

        $stateWhere = '';
        $stateCount = count($states);

        foreach ($states as $stateIndex => $state) {
            $stateWhere .= 'CrawlJobContainer.state = :State' . $stateIndex;
            if ($stateIndex < $stateCount - 1) {
                $stateWhere .= ' OR ';
            }
            $queryBuilder->setParameter('State'.$stateIndex, $state);
        }

        $where .= ' AND ('.$stateWhere.')';
        
        $queryBuilder->where($where);
        $queryBuilder->setParameter('Job', $job);                        

        return $queryBuilder->getQuery()->getResult();        
    }
}