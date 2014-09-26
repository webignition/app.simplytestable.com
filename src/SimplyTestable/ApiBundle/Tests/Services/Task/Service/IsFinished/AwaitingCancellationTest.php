<?php

namespace SimplyTestable\ApiBundle\Tests\Services\Task\Service\IsFinished;

class AwaitingCancellationTest extends isFinishedTest {    
    
    protected function getExpectedIsFinished() {
        return false;
    }

}
