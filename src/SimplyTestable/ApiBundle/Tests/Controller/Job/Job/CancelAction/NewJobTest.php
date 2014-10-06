<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Job\Job\CancelAction;

class NewJobTest extends IsCancelledTest {

    protected function getJob() {
        return $this->getJobService()->getById($this->createJobAndGetId(self::DEFAULT_CANONICAL_URL));
    }

    protected function getExpectedJobStartingState() {
        return $this->getJobService()->getStartingState();
    }

}


