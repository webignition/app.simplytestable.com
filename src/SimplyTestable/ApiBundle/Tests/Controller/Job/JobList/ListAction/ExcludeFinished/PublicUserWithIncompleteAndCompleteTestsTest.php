<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\ListAction\ExcludeFinished;

class PublicUserWithIncompleteAndCompleteTestsTest extends StateBasedTest {

    protected function getRequestingUser() {
        return $this->getUserService()->getPublicUser();
    }

    protected function getExpectedListLength() {
        return count($this->getCanonicalUrls()) - count($this->getJobService()->getFinishedStates());
    }

    protected function getCanonicalUrls() {
        return array_merge(
            $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()),
            $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates())
        );
    }

    protected function getExpectedJobListUrls() {
        return array_reverse($this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()));
    }
    
    protected function applyPreListChanges() {
        foreach ($this->jobs as $job) {
            $stateName = str_replace(array('http://', '.example.com/'), '', $job->getWebsite()->getCanonicalUrl());
            $job->setState($this->getStateService()->fetch($stateName));
            $this->getJobService()->persistAndFlush($job);
        }
    }

}


