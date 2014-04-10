<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class FailedRetryLimitReachedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
