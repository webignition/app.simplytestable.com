<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class FailedRetryLimitReachedTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
