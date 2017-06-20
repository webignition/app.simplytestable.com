<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task\TaskService\IsFinished;

class FailedRetryAvailableTest extends IsFinishedTest {

    protected function getExpectedIsFinished() {
        return true;
    }

}
