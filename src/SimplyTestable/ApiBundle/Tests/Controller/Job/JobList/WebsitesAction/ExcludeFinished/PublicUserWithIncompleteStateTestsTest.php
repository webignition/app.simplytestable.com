<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\JobList\WebsitesAction\ExcludeFinished;

class PublicUserWithIncompleteStateTestsTest extends StateBasedTest {

    protected function getExpectedWebsitesList() {
        $expectedWebsitesList = $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates());
        sort($expectedWebsitesList);
        return $expectedWebsitesList;          
    }

    protected function getCanonicalUrls() {
        return $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates());
    }
    
    protected function applyPreListChanges() {
        foreach ($this->jobs as $job) {
            $stateName = str_replace(array('http://', '.example.com/'), '', $job->getWebsite()->getCanonicalUrl());
            $job->setState($this->getStateService()->fetch($stateName));
            $this->getJobService()->persistAndFlush($job);
        }
    }

}


