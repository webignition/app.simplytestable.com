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
        
        $excludeTypeNames = (is_null($this->get('request')->query->get('exclude-types'))) ? array('crawl') : $this->get('request')->query->get('exclude-types');
        if (!in_array('crawl', $excludeTypeNames)) {
            $excludeTypeNames[] = 'crawl';
        }        

        $excludeTypes = array();

        foreach ($excludeTypeNames as $typeName) {
            if ($this->getJobTypeService()->has($typeName)) {
                $excludeTypes[] = $this->getJobTypeService()->getByName($typeName);
            }
        }

        $this->getJobListService()->setExcludeTypes($excludeTypes);

        $excludeStateNames = array();
        if (!is_null($this->get('request')->query->get('exclude-current'))) {            
            foreach ($this->getJobService()->getIncompleteStates() as $state) {
                if (!in_array($state->getName(), $excludeStateNames)) {
                    $excludeStateNames[] = $state->getName();
                }
            }
        }
        
        if (!is_null($this->get('request')->query->get('exclude-finished'))) {            
            foreach ($this->getJobService()->getFinishedStates() as $state) {
                if (!in_array($state->getName(), $excludeStateNames)) {
                    $excludeStateNames[] = $state->getName();
                }
            }
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
        
        $excludeStates = array();
        foreach ($excludeStateNames as $stateName) {
            if ($this->getStateService()->has($stateName)) {
                $excludeStates[] = $this->getStateService()->fetch($stateName);
            }
        } 
        
        $this->getJobListService()->setExcludeStates($excludeStates);        

        $crawlJobParentIds = array();
        $crawlJobContainers = $this->getCrawlJobContainerService()->getAllActiveForUser($this->getUser());
        foreach ($crawlJobContainers as $crawlJobContainer) {
            $crawlJobParentIds[] = $crawlJobContainer->getParentJob()->getId();
        }
        
        if (is_null($this->get('request')->query->get('exclude-current'))) { 
            $this->getJobListService()->setIncludeIds($crawlJobParentIds);
        } else {
            $this->getJobListService()->setExcludeIds($crawlJobParentIds);
        }
        
        $jobs = $this->getJobListService()->get();        
        $summaries = array();
        
        foreach ($jobs as $job) {
            $this->populateJob($job);            
            $summaries[] = $this->getSummary($job);
        }
        
        return $this->sendResponse(array(
            'max_results' => $this->getJobListService()->getMaxResults(),
            'limit' => $this->getJobListService()->getLimit(),
            'offset' => $this->getJobListService()->getOffset(),
            'jobs' => $summaries
        )); 
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
