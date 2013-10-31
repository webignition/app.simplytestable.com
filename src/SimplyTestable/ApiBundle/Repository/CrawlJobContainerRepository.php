<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\User;

class CrawlJobContainerRepository extends EntityRepository
{
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return boolean
     */
    public function hasForJob(Job $job) {
        return !is_null($this->getForJob($job));
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\Job\Job $job
     * @return \SimplyTestable\ApiBundle\Entity\CrawlJobContainer
     */
    public function getForJob(Job $job) {        
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
    
    
    public function getAllForUserByCrawlJobStates(User $user, $states) {
        $queryBuilder = $this->createQueryBuilder('CrawlJobContainer');
        $queryBuilder->join('CrawlJobContainer.parentJob', 'ParentJob');
        $queryBuilder->join('CrawlJobContainer.crawlJob', 'CrawlJob');        
        $queryBuilder->select('CrawlJobContainer');
        
        $stateWhereParts = array();
        
        foreach ($states as $stateIndex => $state) {            
            $stateWhereParts[] = 'CrawlJob.state = :State' . $stateIndex;
            $queryBuilder->setParameter('State' . $stateIndex, $state);
        }
        
        $queryBuilder->where('CrawlJob.user = :User AND ('.implode(' OR ', $stateWhereParts).')');
        $queryBuilder->setParameter('User', $user);        
        
        return $queryBuilder->getQuery()->getResult();         
    }
}