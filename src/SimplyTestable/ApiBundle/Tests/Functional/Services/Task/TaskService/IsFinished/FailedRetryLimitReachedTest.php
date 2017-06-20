<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class FailedRetryLimitReachedTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
