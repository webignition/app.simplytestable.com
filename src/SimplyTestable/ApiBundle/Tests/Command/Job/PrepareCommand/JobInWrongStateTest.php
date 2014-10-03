<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand;

class JobInWrongStateTest extends CommandTest {

    protected function preCall() {
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());
    }

    protected function getJob() {
        return $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL));
    }

    protected function getExpectedReturnCode() {
        return 1;
    }
}
