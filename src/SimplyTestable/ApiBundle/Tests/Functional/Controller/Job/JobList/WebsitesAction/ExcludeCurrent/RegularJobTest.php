<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\JobList\WebsitesAction\ExcludeCurrent;

class RegularJobTest extends ExcludeCurrentTest {

    protected function getCanonicalUrls() {
        return array_merge(
            $this->getStateBasedCanonicalUrls($this->getJobService()->getIncompleteStates()),
            $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates())
        );
    }

    protected function getExpectedWebsitesList() {
        $expectedWebsitesList = $this->getStateBasedCanonicalUrls($this->getJobService()->getFinishedStates());
        sort($expectedWebsitesList);

        return $expectedWebsitesList;
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