<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class PublicUserWithIncompleteStateTestsTest extends StateBasedTest {

    protected function getExpectedListLength() {
        return count($this->getCanonicalUrls());
    }

    protected function getCanonicalUrls() {
        return $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates());
    }

    protected function getExpectedJobListUrls() {
        return array_reverse($this->getCanonicalUrls());
    }
    
    protected function applyPreListChanges() {
        foreach ($this->jobs as $job) {
            $stateName = str_replace(array('http://', '.example.com/'), '', $job->getWebsite()->getCanonicalUrl());
            $job->setState($this->getStateService()->fetch($stateName));
            $this->getJobService()->persistAndFlush($job);
        }
    }

}


