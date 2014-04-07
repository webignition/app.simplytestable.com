<?php

namespace SimplyTestable\ApiBundle\Controller\Job;

class JobListController extends BaseJobController
{
    
    public function listAction($limit = null, $offset = null)
    {
        $this->getJobListService()->setUser($this->getUser());
        
        $this->getJobListService()->setLimit($limit);
        $this->getJobListService()->setOffset($offset);
        $this->getJobListService()->setOrderBy($this->get('request')->query->get('order-by'));     
        $this->getJobListService()->setExcludeTypes($this->getExcludeTypes());        
        $this->getJobListService()->setExcludeStates($this->getExcludeStates());
        $this->getJobListService()->setUrlFilter($this->get('request')->query->get('url-filter'));        
        
        if ($this->shouldExcludeCurrent()) {
            $this->getJobListService()->setExcludeIds($this->getCrawlJobParentIds());
        } else {
            $this->getJobListService()->setIncludeIds($this->getCrawlJobParentIds());
        }
        
        return $this->sendResponse(array(
            'max_results' => $this->getJobListService()->getMaxResults(),
            'limit' => $this->getJobListService()->getLimit(),
            'offset' => $this->getJobListService()->getOffset(),
            'jobs' => $this->getJobSummaries()
        )); 
    }
    
    
    /**
     * 
     * @return array
     */
    private function getJobSummaries() {
        $jobs = $this->getJobListService()->get();        
        $summaries = array();
        
        foreach ($jobs as $job) {      
            $summaries[] = $this->getSummary($this->populateJob($job));
        }
        
        return $jobs;
    }
    
    
    /**
     * @return int[]
     */
    private function getCrawlJobParentIds() {
        $crawlJobParentIds = array();
        $crawlJobContainers = $this->getCrawlJobContainerService()->getAllActiveForUser($this->getUser());
        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobParentIds[] = $crawlJobContainer->getParentJob()->getId();
        }   
        
        return $crawlJobParentIds;
    }
    
    
    /**
     * 
     * @return string[]
     */
    private function getExcludeTypeNames() {
        $excludeTypeNames = (is_null($this->get('request')->query->get('exclude-types'))) ? array('crawl') : $this->get('request')->query->get('exclude-types');
        if (!in_array('crawl', $excludeTypeNames)) {
            $excludeTypeNames[] = 'crawl';
        }   
        
        return $excludeTypeNames;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\Job\Type[]
     */
    private function getExcludeTypes() {
        $excludeTypes = array();

        foreach ($this->getExcludeTypeNames() as $typeName) {
            if ($this->getJobTypeService()->has($typeName)) {
                $excludeTypes[] = $this->getJobTypeService()->getByName($typeName);
            }
        }
        
        return $excludeTypes;
    }
    
    
    /**
     * 
     * @return boolean
     */
    private function shouldExcludeCurrent() {
        return !is_null($this->get('request')->query->get('exclude-current'));
    }
    
    
    /**
     * 
     * @return string[]
     */
    private function getExcludeStateNames() {
        $excludeStateNames = array();
        if ($this->shouldExcludeCurrent()) {            
            $excludeStateNames = array_merge($excludeStateNames, $this->getStateNames($this->getJobService()->getIncompleteStates()));
        }
        
        if (!is_null($this->get('request')->query->get('exclude-finished'))) {            
            $excludeStateNames = array_merge($excludeStateNames, $this->getStateNames($this->getJobService()->getFinishedStates()));
        }        
        
        if (!is_null($this->get('request')->query->get('exclude-states'))) {
            $truncatedStateNames = $this->get('request')->query->get('exclude-states');
            foreach ($truncatedStateNames as $truncatedStateName) {
                $stateName = 'job-' . $truncatedStateName;
                if (!in_array($stateName, $excludeStateNames)) {
                    $excludeStateNames[] = $stateName;
                }
            }
        }  
        
        return $excludeStateNames;
    }
    
    
    /**
     * 
     * @return \SimplyTestable\ApiBundle\Entity\State[]
     */
    private function getExcludeStates() {
        $excludeStates = array();
        foreach ($this->getExcludeStateNames() as $stateName) {
            if ($this->getStateService()->has($stateName)) {
                $excludeStates[] = $this->getStateService()->fetch($stateName);
            }
        }
        
        return $excludeStates;
    }
    
    
    /**
     * 
     * @param array $states
     * @return string[]
     */
    private function getStateNames($states) {
        $stateNames = array();
        
        foreach ($states as $state) {
            if (!in_array($state->getName(), $stateNames)) {
                $stateNames[] = $state->getName();
            }
        }          
        
        return $stateNames;
    }
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\StateService
     */        
    private function getStateService() {
        return $this->get('simplytestable.services.stateservice');
    }    
    
    
    /**
     *
     * @return \SimplyTestable\ApiBundle\Services\JobListService
     */        
    private function getJobListService() {
        return $this->get('simplytestable.services.joblistservice');
    }        
}
