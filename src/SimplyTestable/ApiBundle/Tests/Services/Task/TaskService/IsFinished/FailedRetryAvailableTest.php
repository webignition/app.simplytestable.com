<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}