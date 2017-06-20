<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedNoRetryAvailableTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
