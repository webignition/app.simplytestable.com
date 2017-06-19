<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedRetryAvailableTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
