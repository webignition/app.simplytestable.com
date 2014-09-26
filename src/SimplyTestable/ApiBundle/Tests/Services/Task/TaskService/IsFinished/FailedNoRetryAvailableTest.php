<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\TaskService\IsFinished;

class FailedNoRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
