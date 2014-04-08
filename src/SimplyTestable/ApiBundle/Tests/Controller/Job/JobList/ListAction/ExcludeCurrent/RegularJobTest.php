<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeCurrent;

class RegularJobTest extends ExcludeCurrentTest {    
    
    protected function getCanonicalUrls() {
        return array_merge(
            $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()),
            $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates())
        );
    }

    protected function getExpectedJobListUrls() {
        return array_reverse($this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates()));
    }

    protected function getExpectedListLength() {
        return count($this->getExpectedJobListUrls());
    }
    
    private function getStateBasedCanonicalUrls($states) {
        $canonicalUrls = array();
        
        foreach ($states as $state) {
            $canonicalUrls[] = 'http://' . $state->getName() . '.example.com/';
        }
        
        return $canonicalUrls;
    }
    
    protected function applyPreListChanges() {
        foreach ($this->jobs as $job) {
            $stateName = str_replace(array('http://', '.example.com/'), '', $job->getWebsite()->getCanonicalUrl());
            $job->setState($this->getStateService()->fetch($stateName));
            $this->getJobService()->persistAndFlush($job);
        }
    }    

}