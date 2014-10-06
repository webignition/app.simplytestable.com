<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class CancelParentJobOfCrawlJobTest extends IsCancelledTest {

    private $user;
    private $crawlJobContainer;

    protected function preCall() {
        $this->getUserService()->setUser($this->getUser());

        $this->crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($this->job);
        $this->getCrawlJobContainerService()->prepare($this->crawlJobContainer);
    }


    public function testCrawlJobIsCancelled() {
        $this->assertEquals($this->getJobService()->getCancelledState(), $this->crawlJobContainer->getCrawlJob()->getState());
    }

    protected function getJob() {
        $job = $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL, $this->getUser()->getEmail()));

        $job->setState($this->getJobService()->getFailedNoSitemapState());
        $this->getJobService()->persistAndFlush($job);

        return $job;
    }

    protected function getExpectedJobStartingState() {
        return $this->getJobService()->getFailedNoSitemapState();
    }

    protected function getExpectedResponseCode() {
        return 200;
    }

    private function getUser() {
        if (is_null($this->user)) {
            $this->user = $this->createAndActivateUser('user@example.com');
        }

        return $this->user;
    }
    
}


