<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedRetryLimitReachedTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
