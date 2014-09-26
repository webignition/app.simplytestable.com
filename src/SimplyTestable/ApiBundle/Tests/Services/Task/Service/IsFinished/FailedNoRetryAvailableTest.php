<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class FailedNoRetryAvailableTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return true;
    }

}
