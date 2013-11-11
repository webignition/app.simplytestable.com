<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\State;

class JobListService  {
    
    const DEFAULT_LIMIT = 1;
    const DEFAULT_OFFSET = 0;    
    
    const DEFAULT_ORDER_BY = 'id';
    
    private $orderByFieldMap = array(
        'id' => 'Job.id'
    );
    
    
    /**
     *
     * @var \SimplyTestable\ApiBundle\Services\JobService
     */
    private $jobService;
    
    
    /**
     *
     * @var int
     */
    private $limit = null;
    
    
    /**
     *
     * @var int
     */
    private $offset = null;
    
    
    /**
     *
     * @var User
     */
    private $user = null;
    
    
    /**
     *
     * @var string
     */
    private $orderBy =  null;
    
    
    /**
     *
     * @var boolean
     */
    private $sortDescending = true;
    
    
    /**
     * Collection of JobTypes to exclude from list
     *  
     * @var array
     */
    private $excludeTypes = array();
    
    
    /**
     * Collection of Job States to exclude from list
     * 
     * @var array
     */
    private $excludeStates = array();
    
    
    /**
     * Collection of ids of jobs to include if not otherwise included
     *
     * @var array
     */
    private $includeIds = array();
    
    
    /**
     * Explicitly exclude jobs by id
     * 
     * @var boolean
     */
    private $excludeIds = array();
    
    
    /**
     *
     * @param \SimplyTestable\ApiBundle\Services\JobService $jobService
     */
    public function __construct(
            \SimplyTestable\ApiBundle\Services\JobService $jobService)
    {
        $this->jobService = $jobService;
    }
    
    
    /**
     * 
     * @param int $limit
     */
    public function setLimit($limit) {        
        $limit = filter_var($limit, FILTER_VALIDATE_INT, array(
            'options' => array(
                'min_range' => 1
        )));        
        
        if ($limit === false) {
            $limit = self::DEFAULT_LIMIT;
        }
        
        $this->limit = $limit;
    }
    
    
    /**
     * 
     * @param int $offset
     */
    public function setOffset($offset) {
        $this->offset = $offset;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getLimit() {
        return (is_null($this->limit)) ? self::DEFAULT_LIMIT : $this->limit;
    }
    
    
    /**
     * 
     * @return int
     */
    public function getOffset() {
        return (is_null($this->offset)) ? self::DEFAULT_OFFSET : $this->offset;
    }
    
    
    /**
     * 
     * @param \SimplyTestable\ApiBundle\Entity\User $user
     */
    public function setUser(User $user) {
        $this->user = $user;
    }
    
    
    /**
     * 
     * @param string $orderBy
     */
    public function setOrderBy($orderBy) {
        $this->orderBy = $orderBy;
    }
    
    
    public function setSortAscending() {
        $this->sortDescending = false;
    }    
    
    
    public function setSortDescending() {
        $this->sortDescending = true;
    }
    
    
    public function setExcludeTypes($excludeTypes) {
        $this->excludeTypes = array();
        
        foreach ($excludeTypes as $jobType) {
            if ($jobType instanceof JobType) {
                $this->excludeTypes[] = $jobType;
            }
        }
    }
    
    
    public function setExcludeStates($excludeStates) {
        $this->excludeStates = array();
        
        foreach ($excludeStates as $state) {
            if ($state instanceof State) {
                $this->excludeStates[] = $state;
            }
        }
    }
    
    
    /**
     * 
     * @param array $includeIds
     */
    public function setIncludeIds($includeIds) {
        $this->includeIds = array();
        
        foreach ($includeIds as $jobId) {
            $job = $this->getJobService()->getById($jobId);
            if (!$this->isExcluded($job)) {
                $this->includeIds[] = $job->getId();
            }
        }
    }    
    
    
    /**
     * 
     * @param array $excludeIds
     */
    public function setExcludeIds($excludeIds) {
        $this->excludeIds = array();
        
        foreach ($excludeIds as $jobId) {
            $job = $this->getJobService()->getById($jobId);
            $this->excludeIds[] = $job->getId();
        }
    }
    
    
    private function isExcluded(Job $job) {
//        foreach ($this->excludeStates as $state) {
//            if ($job->getState()->equals($state)) {
//                return true;
//            }
//        }
//        
//        foreach ($this->excludeTypes as $type) {
//            if ($job->getType()->equals($type)) {
//                return true;
//            }
//        }
        
//        foreach ($this->excludeIds as $id) {
//            if ($job->getId() == $id) {
//                return true;
//            }
//        }
        
        return false;
    }
    
    
    /**
     * 
     * @return array
     */
    public function get() {
        $queryBuilder = $this->getQueryBuilder();
        
        $queryBuilder->select('Job');
        $queryBuilder->where('Job.user = :User');
        $queryBuilder->setParameter('User', $this->user);
        
        if (is_array($this->excludeTypes) && count($this->excludeTypes) > 0) {            
            $typeExclusionParts = array();
            
            foreach ($this->excludeTypes as $typeIndex => $type) {                
                $typeExclusionParts[] = 'Job.type != :Type' .  $typeIndex;
                $queryBuilder->setParameter('Type' .  $typeIndex, $type);
            }
            
            $queryBuilder->andWhere('('.implode(' AND ', $typeExclusionParts).')');
        }
        
        if (is_array($this->excludeStates) && count($this->excludeStates) > 0) {            
            $stateExclusionParts = array();
            
            foreach ($this->excludeStates as $stateIndex => $state) {                
                $stateExclusionParts[] = 'Job.state != :State' .  $stateIndex;
                $queryBuilder->setParameter('State' .  $stateIndex, $state);
            }
            
            $queryBuilder->andWhere('('.implode(' AND ', $stateExclusionParts).')');
        }
        
        if (is_array($this->includeIds) && count($this->includeIds) > 0) {
            $idWhereParts = array();
            
            foreach ($this->includeIds as $idIndex => $id) {
                $idWhereParts[] = 'Job.id = :Id' . $idIndex;
                $queryBuilder->setParameter('Id' .  $idIndex, $id);
            }
            
            $queryBuilder->orWhere(implode(' OR ', $idWhereParts));
        }
        
        if (is_array($this->excludeIds) && count($this->excludeIds) > 0) {            
            $idWhereParts = array();
            
            foreach ($this->excludeIds as $idIndex => $id) {
                $idWhereParts[] = 'Job.id != :Id' . $idIndex;
                $queryBuilder->setParameter('Id' .  $idIndex, $id);
            }
            
            $queryBuilder->andWhere(implode(' AND ', $idWhereParts));
        }        
        
        $queryBuilder->orderBy($this->getOrderByField(), $this->getOrder());
        
        $queryBuilder->setMaxResults($this->getLimit());
        $queryBuilder->setFirstResult($this->getOffset());
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    
    /**
     * 
     * @return string
     */
    private function getOrder() {
        return $this->sortDescending ? 'DESC' : 'ASC';
    }
    

    /**
     * 
     * @return string
     */
    private function getOrderBy() {
        return (is_null($this->orderBy)) ? self::DEFAULT_ORDER_BY : $this->orderBy;
    }
    
    
    /**
     * 
     * @return string
     */
    private function getOrderByField() {
        return isset($this->orderByFieldMap[$this->getOrderBy()]) ? $this->orderByFieldMap[$this->getOrderBy()] : $this->orderByFieldMap[self::DEFAULT_ORDER_BY];
    }
    
    
/**
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
 */    
    
    
    /**
     * 
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQueryBuilder() {
        return $this->getJobService()->getEntityRepository()->createQueryBuilder('Job');
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->jobService;
    }
}