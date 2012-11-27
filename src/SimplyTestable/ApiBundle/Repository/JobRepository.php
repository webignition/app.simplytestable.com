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
    public function findAllByUserOrderedByIdDesc(User $user, $limit = null)
    {        
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.user = :User';
        
        $queryBuilder->where($where);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->orderBy('Job.id', 'DESC');

        $queryBuilder->setParameter('User', $user);
        return $queryBuilder->getQuery()->getResult();         
    }
    
    
    /**
     * 
     * @param int $id
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $users
     * @return Job
     */
    public function findByIdAndWebsiteAndUsers($id, WebSite $website, $users = array()) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.id = :Id AND Job.website = :Website';
        
        if (is_array($users)) {
            $userWhere = '';
            $userCount = count($users);
            
            foreach ($users as $userIndex => $user) {
                $userWhere .= 'Job.user = :User' . $userIndex;
                if ($userIndex < $userCount - 1) {
                    $userWhere .= ' OR ';
                }
                $queryBuilder->setParameter('User'.$userIndex, $user);
            }
            
            $where .= ' AND ('.$userWhere.')';
        }
        
        $queryBuilder->where($where);
        $queryBuilder->setMaxResults(1);

        $queryBuilder->setParameter('Id', $id);
        $queryBuilder->setParameter('Website', $website);
        $result = $queryBuilder->getQuery()->getResult(); 
        
        return (count($result) > 0) ? $result[0] : null;
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
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $jobStates
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @return int
     */
    public function getAllByWebsiteAndStateAndUser(WebSite $website, $jobStates, User $user) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
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
        $queryBuilder->orderBy('Job.id', 'desc');

        $queryBuilder->setParameter('Website', $website);
        $queryBuilder->setParameter('User', $user);
        $result = $queryBuilder->getQuery()->getResult();
        
        return (count($result)) ? $result : null;
    }     
}