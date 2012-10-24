<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Entity\User;

class JobRepository extends EntityRepository
{
    
    /**
     * 
     * @param $limit int
     * @return array
     */
    public function findAllOrderedByIdDesc($limit = null)
    {  
        $query = $this->getEntityManager()->createQuery('SELECT j FROM SimplyTestableApiBundle:Job\Job j ORDER BY j.id DESC');
        
        if (!is_null($limit)) {
            $query->setMaxResults($limit);
        }
        
        return $query->getResult();
    }
    
    
    /**
     *
     * @param array $jobStates
     * @param State $taskState
     * @return array 
     */
    public function getByStateAndTaskState($jobStates, State $taskState) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->join('Job.tasks', 'Tasks');
        $queryBuilder->select('DISTINCT Job');
        
        $where = 'Tasks.state = :TaskState';
        
        if (is_array($jobStates)) {
            $stateWhere = '';
            $stateCount = count($jobStates);
            
            foreach ($jobStates as $stateIndex => $jobState) {
                $stateWhere .= 'Job.state = :JobState' . $stateIndex;
                if ($stateIndex < $stateCount - 1) {
                    $stateWhere .= ' OR ';
                }
                $queryBuilder->setParameter('JobState'.$stateIndex, $jobState);
            }
            
            $where .= ' AND ('.$stateWhere.')';
        }
        
        $queryBuilder->where($where);

        $queryBuilder->setParameter('TaskState', $taskState);                
        return $queryBuilder->getQuery()->getResult();
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $jobStates
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return int
     */
    public function getNewestIdByWebsiteAndStateAndUser(WebSite $website, $jobStates, User $user) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');
        
        $where = 'Job.website = :Website and Job.user = :User';
        
        if (is_array($jobStates)) {
            $stateWhere = '';
            $stateCount = count($jobStates);
            
            foreach ($jobStates as $stateIndex => $jobState) {
                $stateWhere .= 'Job.state = :JobState' . $stateIndex;
                if ($stateIndex < $stateCount - 1) {
                    $stateWhere .= ' OR ';
                }
                $queryBuilder->setParameter('JobState'.$stateIndex, $jobState);
            }
            
            $where .= ' AND ('.$stateWhere.')';
        }
        
        $queryBuilder->where($where);
        $queryBuilder->setMaxResults(1);
        $queryBuilder->orderBy('Job.id', 'desc');

        $queryBuilder->setParameter('Website', $website);
        $queryBuilder->setParameter('User', $user);
        $result = $queryBuilder->getQuery()->getResult();
        
        return (count($result)) ? (int)$result[0]['id'] : null;
    }
}