<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class SuccessfulPrepareTest extends CommandTest {

    protected function preCall() {
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());
    }

    protected function getJob() {
        return $this->getJobService()->getById($this->createAndResolveDefaultJob());
    }

    protected function getExpectedReturnCode() {
        return 0;
    }
}
