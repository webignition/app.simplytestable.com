<?php
namespace SimplyTestable\ApiBundle\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Job\Type as JobType;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Entity\State;

class JobListService  {
    
    const DEFAULT_LIMIT = 1;
    const MIN_LIMIT = 1;
    const MAX_LIMIT = 100;
    
    const DEFAULT_OFFSET = 0; 
    const MIN_OFFSET = 0;
    
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
     * @var string
     */
    private $urlFilter = null;
    
    
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
        $limit = (int)filter_var($limit, FILTER_VALIDATE_INT);
        
        if ($limit < self::MIN_LIMIT) {
            $limit = self::MIN_LIMIT;
        }
        
        if ($limit > self::MAX_LIMIT) {
            $limit = self::MAX_LIMIT;
        }
        
        $this->limit = $limit;
    }
    
    
    /**
     * 
     * @param int $offset
     */
    public function setOffset($offset) {
        $offset = (int)filter_var($offset, FILTER_VALIDATE_INT);        
        
        if ($offset < self::MIN_OFFSET) {
            $offset = self::MIN_OFFSET;
        }
        
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
    
    
    /**
     * 
     * @param string $filter
     */
    public function setUrlFilter($filter) {
        $this->urlFilter = $filter;
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
        $queryBuilder = $this->getDefaultQueryBuilder();
        
        $queryBuilder->setMaxResults($this->getLimit());
        $queryBuilder->setFirstResult($this->getOffset());
        
        $queryBuilder->select('Job');
        
        return $queryBuilder->getQuery()->getResult();
    }
    
    
    public function getMaxResults() {
        $queryBuilder = $this->getDefaultQueryBuilder();
        
        $queryBuilder->select('COUNT(Job.id)');
        
        $result = $queryBuilder->getQuery()->getResult();
        
        return (int)$result[0][1];    
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasUser() {
        return !is_null($this->user);
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasExcludeTypes() {
        return is_array($this->excludeTypes) && count($this->excludeTypes) > 0;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasExcludeStates() {
        return is_array($this->excludeStates) && count($this->excludeStates) > 0;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasIncludeIds() {
        return is_array($this->includeIds) && count($this->includeIds) > 0;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasExcludeIds() {
        return is_array($this->excludeIds) && count($this->excludeIds) > 0;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function hasUrlFilter() {
        return !is_null($this->urlFilter);
    }    
    
    
    /**
     * 
     * @return \Doctrine\ORM\QueryBuilder
     */    
    private function getDefaultQueryBuilder() {
        $queryBuilder = $this->getQueryBuilder();
        
        if ($this->hasUser()) {
            $this->setQueryUserFilter($queryBuilder);           
        }
        
        if ($this->hasExcludeTypes()) {            
            $this->setQueryTypeExclusion($queryBuilder);
        }
        
        if ($this->hasExcludeStates()) {            
            $this->setQueryStateExclusion($queryBuilder);
        }
        
        if ($this->hasIncludeIds()) {
            $this->setQueryIncludeIds($queryBuilder);
        }
        
        if ($this->hasExcludeIds()) {            
            $this->setQueryExcludeIds($queryBuilder);
        }  
        
        if ($this->hasUrlFilter()) {
            $this->setQueryUrlFilter($queryBuilder);
        }
        
        $queryBuilder->orderBy($this->getOrderByField(), $this->getOrder());        
        
        return $queryBuilder;
    }
    
    
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function setQueryUserFilter(\Doctrine\ORM\QueryBuilder $queryBuilder) { 
        $queryBuilder->andWhere('Job.user = :User');
        $queryBuilder->setParameter('User', $this->user);
    }
    

    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function setQueryTypeExclusion(\Doctrine\ORM\QueryBuilder $queryBuilder) {
        $typeExclusionParts = array();

        foreach ($this->excludeTypes as $typeIndex => $type) {                
            $typeExclusionParts[] = 'Job.type != :Type' .  $typeIndex;
            $queryBuilder->setParameter('Type' .  $typeIndex, $type);
        }

        $queryBuilder->andWhere('('.implode(' AND ', $typeExclusionParts).')');        
    }
    
    
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function setQueryStateExclusion(\Doctrine\ORM\QueryBuilder $queryBuilder) {
        $stateExclusionParts = array();

        foreach ($this->excludeStates as $stateIndex => $state) {                
            $stateExclusionParts[] = 'Job.state != :State' .  $stateIndex;
            $queryBuilder->setParameter('State' .  $stateIndex, $state);
        }

        $queryBuilder->andWhere('('.implode(' AND ', $stateExclusionParts).')');        
    }
    

    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */    
    private function setQueryIncludeIds(\Doctrine\ORM\QueryBuilder $queryBuilder) {
        $idWhereParts = array();

        foreach ($this->includeIds as $idIndex => $id) {
            $idWhereParts[] = 'Job.id = :Id' . $idIndex;
            $queryBuilder->setParameter('Id' .  $idIndex, $id);
        }

        $queryBuilder->orWhere(implode(' OR ', $idWhereParts));        
    }
    
  
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function setQueryExcludeIds(\Doctrine\ORM\QueryBuilder $queryBuilder) {
        $idWhereParts = array();

        foreach ($this->excludeIds as $idIndex => $id) {
            $idWhereParts[] = 'Job.id != :Id' . $idIndex;
            $queryBuilder->setParameter('Id' .  $idIndex, $id);
        }

        $queryBuilder->andWhere(implode(' AND ', $idWhereParts));        
    }
    
    
    /**
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     */
    private function setQueryUrlFilter(\Doctrine\ORM\QueryBuilder $queryBuilder) {
        $queryBuilder->join('Job.website', 'Website');

        if (substr_count($this->urlFilter, '*')) {
            $queryBuilder->andWhere('Website.canonicalUrl LIKE :Website');
            $queryBuilder->setParameter('Website', str_replace('*', '%', $this->urlFilter));               
        } else {
            $queryBuilder->andWhere('Website.canonicalUrl = :Website');
            $queryBuilder->setParameter('Website', $this->urlFilter);                
        }        
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
     * 
     * @return \Doctrine\ORM\QueryBuilder
     */
    private function getQueryBuilder() {
        $queryBuilder = $this->getJobService()->getEntityRepository()->createQueryBuilder('Job');        
        $queryBuilder->where('1 = 1');
        
        return $queryBuilder;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Services\JobService
     */
    private function getJobService() {
        return $this->jobService;
    }
}