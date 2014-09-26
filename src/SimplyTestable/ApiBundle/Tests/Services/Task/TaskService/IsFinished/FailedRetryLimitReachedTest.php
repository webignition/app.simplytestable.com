<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedRetryLimitReachedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
