<?php
namespace SimplyTestable\ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
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
    
    
    public function findAllByUserAndNotTypeAndNotStatesOrderedByIdDesc(User $user, $limit = null, $excludeTypes = array(), $excludeStates = array()) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.user = :User';
        
        if (is_array($excludeTypes) && count($excludeTypes) > 0) {            
            $typeExclusionParts = array();
            
            foreach ($excludeTypes as $typeIndex => $type) {
                $typeExclusionParts[] = 'Job.type != :Type' .  $typeIndex;
                $queryBuilder->setParameter('Type' .  $typeIndex, $type);
            }

            $where .= ' AND ('.implode(' AND ', $typeExclusionParts).')';
        }
        
        if (is_array($excludeStates) && count($excludeStates) > 0) {            
            $stateExclusionParts = array();
            
            foreach ($excludeStates as $stateIndex => $state) {                
                $stateExclusionParts[] = 'Job.state != :State' .  $stateIndex;
                $queryBuilder->setParameter('State' .  $stateIndex, $state);
            }

            $where .= ' AND ('.implode(' AND ', $stateExclusionParts).')';
        }
        
        $queryBuilder->where($where);
        $queryBuilder->setMaxResults($limit);
        $queryBuilder->orderBy('Job.id', 'DESC');

        $queryBuilder->setParameter('User', $user);
        return $queryBuilder->getQuery()->getResult();          
    }
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @param array $users
     * @return Job
     */
    public function findLatestByWebsiteAndUsers(WebSite $website, $users = array()) {                
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.website = :Website';
        
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
        $queryBuilder->orderBy('Job.id', 'DESC');

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
     * @param State $state
     * @return array 
     */
    public function getIdsByState(State $state) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');     
        $queryBuilder->where('Job.state = :State');

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
     * @param State $state
     * @return int 
     */
    public function getCountByState(State $state) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('COUNT(Job.id)');     
        $queryBuilder->where('Job.state = :State');

        $queryBuilder->setParameter('State', $state);                
        
        $result = $queryBuilder->getQuery()->getResult();
        
        return (int)$result[0][1];
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
     * @param \SimplyTestable\ApiBundle\Entity\Job\Type $type
     * @return int
     */
    public function getAllByWebsiteAndStateAndUserAndType(WebSite $website, $jobStates, User $user, JobType $type) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.website = :Website and Job.user = :User and Job.type = :Type';
        
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
        $queryBuilder->setParameter('Type', $type);
        $result = $queryBuilder->getQuery()->getResult();
        
        return (count($result)) ? $result : null;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Job\Type $jobType
     * * @param \SimplyTestable\ApiBundle\Entity\WebSite $website
     * @return int
     */
    public function getJobCountByUserAndJobTypeAndWebsiteForCurrentMonth(User $user, JobType $jobType, WebSite $website) {
        $now = new \ExpressiveDate();
        
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('count(Job.id)');
        $queryBuilder->join('Job.timePeriod', 'TimePeriod');
        
        $queryBuilder->where('Job.user = :User AND Job.type = :JobType AND Job.website = :Website AND (TimePeriod.startDateTime >= :StartOfMonth and TimePeriod.startDateTime <= :EndOfMonth)');
        
        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('JobType', $jobType);
        $queryBuilder->setParameter('Website', $website);
        $queryBuilder->setParameter('StartOfMonth', $now->format('Y-m-01'));
        $queryBuilder->setParameter('EndOfMonth', $now->format('Y-m-'.$now->getDaysInMonth()).' 23:59:59');
        
        $result = $queryBuilder->getQuery()->getResult();
        
        return (int)$result[0][1];
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     * @param \SimplyTestable\ApiBundle\Entity\Job\Type $type
     * @param array $jobStates
     * @return array
     */
    public function getIdsByUserAndTypeAndNotStates(User $user, JobType $type, $excludeStates = array()) {        
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.id');
        
        $where = 'Job.user = :User and Job.type = :Type';
        
        if (is_array($excludeStates)) {
            foreach ($excludeStates as $stateIndex => $state) {
                $where .= ' AND Job.state != :State' . $stateIndex;
                $queryBuilder->setParameter('State'.$stateIndex, $state);
            }
        }
        
        $queryBuilder->where($where);
        $queryBuilder->orderBy('Job.id', 'asc');

        $queryBuilder->setParameter('User', $user);
        $queryBuilder->setParameter('Type', $type);
        $result = $queryBuilder->getQuery()->getResult();
        
        return $this->getSingleFieldCollectionFromResult($result, 'id');
    }   
    
    
    private function getSingleFieldCollectionFromResult($result, $fieldName) {
        $collection = array();
        
        foreach ($result as $resultItem) {
            $collection[] = $resultItem[$fieldName];
        }
        
        return $collection;
    }
    
    
    /**
     * 
     * @param int $jobId
     * @return boolean
     */
    public function getIsPublicByJobId($jobId) {        
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job.isPublic');
        
        $queryBuilder->where('Job.id = :JobId');
        $queryBuilder->setParameter('JobId', $jobId, \Doctrine\DBAL\Types\Type::INTEGER);
        
        $result = $queryBuilder->getQuery()->getResult();
        
        if (count($result) === 0) {
            return false;
        }
        
        return $result[0]['isPublic'] === true;        
    }
    

    public function findAllByUserAndTypeAndStates(User $user, $jobTypes, $limit = null, $includeStates = array()) {
        $queryBuilder = $this->createQueryBuilder('Job');
        $queryBuilder->select('Job');
        
        $where = 'Job.user = :User';
        
        $typeWhereParts = array();
        foreach ($jobTypes as $typeIndex => $jobType) {
            $typeWhereParts[] = 'Job.type = :Type' . $typeIndex;
            $queryBuilder->setParameter('Type' . $typeIndex, $jobType);
        }
        
        $where .= ' AND ('.  implode(' OR ', $typeWhereParts).')';
        
        if (is_array($includeStates)) {
            $stateWhere = '';
            $stateCount = count($includeStates);
            
            foreach ($includeStates as $stateIndex => $jobState) {
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
        
        if (!is_null($limit)) {
            $queryBuilder->setMaxResults($limit);
        }

        $queryBuilder->setParameter('User', $user);
        return $queryBuilder->getQuery()->getResult();
    }    
}