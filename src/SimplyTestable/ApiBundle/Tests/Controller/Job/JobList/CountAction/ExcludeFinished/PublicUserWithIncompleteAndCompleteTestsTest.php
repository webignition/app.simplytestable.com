<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\CountAction\ExcludeFinished;

class PublicUserWithIncompleteAndCompleteTestsTest extends StateBasedTest {

    protected function getExpectedCountValue() {
        return count($this->getCanonicalUrls()) - count($this->getJobService()->getFinishedStates());
    }

    protected function getCanonicalUrls() {
        return array_merge(
            $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()),
            $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates())
        );
    }
    
    protected function applyPreListChanges() {
        foreach ($this->jobs as $job) {
            $stateName = str_replace(array('http://', '.example.com/'), '', $job->getWebsite()->getCanonicalUrl());
            $job->setState($this->getStateService()->fetch($stateName));
            $this->getJobService()->persistAndFlush($job);
        }
    }

}


