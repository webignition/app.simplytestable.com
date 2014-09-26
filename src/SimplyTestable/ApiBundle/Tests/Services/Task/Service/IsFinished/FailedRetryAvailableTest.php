<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class FailedRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
