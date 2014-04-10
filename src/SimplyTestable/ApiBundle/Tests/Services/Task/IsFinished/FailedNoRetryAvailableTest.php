<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\IsFinished;

class FailedNoRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
