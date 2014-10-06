<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeCurrent;

class RegularJobTest extends ExcludeCurrentTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }
    
    protected function getCanonicalUrls() {
        return array_merge(
            $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()),
            $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates())
        );
    }

    protected function getExpectedCountValue() {
        return count($this->getJobService()->getFinishedStates());
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