<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class FailedRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
